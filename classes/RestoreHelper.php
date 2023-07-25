<?php declare(strict_types = 1);

namespace mod_edusharing;

use coding_exception;
use dml_exception;
use DOMDocument;
use EduSharingApiClient\Usage;
use Exception;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class RestoreHelper
 */
class RestoreHelper {

    private EduSharingService $service;

    public function __construct(EduSharingService $service) {
        $this->service = $service;
    }

    /**
     * Function convertInlineOptions
     *
     * @param $courseId
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function convertInlineOptions($courseId): void {
        global $DB;
        $sections = $DB->get_records('course_sections', ['course' => $courseId]);
        foreach ($sections as $section) {
            $matchesAtto = $this->getInlineObjects($section->summary);
            if (!empty($matchesAtto)) {
                foreach ($matchesAtto as $match) {
                    $section -> summary = str_replace($match, $this->convertObject($match, $courseId), $section -> summary);
                    $DB->update_record('course_sections', $section);
                }
            }
        }
        $modules = get_course_mods($courseId);
        $course  = get_course($courseId);
        foreach($modules as $module) {
            $modInfo = get_fast_modinfo($course);
            $cm      = $modInfo->get_cm($module->id);
            if(! empty($cm->content)) {
                $matchesAtto = $this->getInlineObjects($cm->content);
                if (!empty($matchesAtto)) {
                    foreach ($matchesAtto as $match) {
                        $cm->set_content(str_replace($match, $this->convertObject($match, $courseId), $cm->content));
                    }
                }
            }
            try {
                $module = $DB->get_record($cm->name, ['id' => $cm->instance], '*', MUST_EXIST);
            } catch (Exception $exception) {
                error_log($exception->getMessage());
                continue;
            }
            if(!empty($module->intro)) {
                $matchesAtto = $this->getInlineObjects($module->intro);
                if (!empty($matchesAtto)) {
                    foreach ($matchesAtto as $match) {
                        $module->intro = str_replace($match, $this->convertObject($match, $courseId), $module->intro);
                    }
                }
            }
            $DB->update_record($cm->name, $module);
        }
        rebuild_course_cache($courseId, true);
    }

    /**
     * Function getInlineObjects
     *
     * @param string $text
     * @return array
     */
    private function getInlineObjects(string $text): array {
        if (!str_contains($text, 'edusharing_atto')) {
            return [];
        }
        if (isloggedin()) {
            try {
                $this->service->getTicket();
            } catch (Exception $exception) {
                trigger_error($exception->getMessage(), E_USER_WARNING);
                return [];
            }
        }
        preg_match_all('#<img(.*)class="(.*)edusharing_atto(.*)"(.*)>#Umsi', $text, $matchesImgAtto, PREG_PATTERN_ORDER);
        preg_match_all('#<a(.*)class="(.*)edusharing_atto(.*)">(.*)</a>#Umsi', $text, $matchesAAtto, PREG_PATTERN_ORDER);
        return array_merge($matchesImgAtto[0], $matchesAAtto[0]);
    }

    /**
     * Function convertObject
     *
     * @param $object
     * @param $courseId
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     * @throws Exception
     */
    private function convertObject($object, $courseId): string {
        global $DB;

        $doc = new DOMDocument();
        $doc->loadHTML($object, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $node = $doc->getElementsByTagName('a')->item(0);
        $type = 'a';
        if (empty($node)) {
            $node = $doc->getElementsByTagName('img')->item(0);
            $qs   = $node->getAttribute('src');
            $type = 'img';
        } else {
            $qs = $node->getAttribute('href');
        }
        if (empty($node)) {
            throw new Exception(get_string('error_loading_node', 'filter_edusharing'));
        }

        $params = array();
        parse_str(parse_url($qs, PHP_URL_QUERY), $params);

        $edusharing = new stdClass();
        $edusharing -> course = $courseId;
        $edusharing -> name = $params['title'];
        $edusharing -> introformat = 0;
        $edusharing -> object_url = $params['object_url'];
        $edusharing -> object_version = $params['window_version'];
        $edusharing -> timecreated = time();
        $edusharing -> timemodified = time();

        $id = $DB -> insert_record('edusharing', $edusharing);

        if($id) {
            $usage = $this->AddUsage($edusharing, $id);
            if ($usage !== null) {
                if (isset($usage->usageId)) {
                    $edusharing->id = $id;
                    $edusharing->usageId = $usage->usageId;
                    $DB->update_record(EDUSHARING_TABLE, $edusharing);
                }
                $params['resourceId'] = $id;
                $url = strtok($qs, '?') . '?';
                foreach ($params as $paramn => $paramv) {
                    $url .= $paramn . '=' . $paramv . '&';
                }
                if ($type === 'a')
                    $node->setAttribute('href', $url);
                else
                    $node->setAttribute('src', $url);

            } else {
                $DB->delete_records('edusharing', array('id' => $id));
                return $object;
            }
        }
        return $doc->saveHTML();
    }

    /**
     * Function
     *
     * @param stdClass $data
     * @param int $newItemId
     * @return Usage|null
     */
    private function addUsage(stdClass $data, int $newItemId): ?Usage {
        $eduService              = new EduSharingService();
        $usageData               = new stdClass();
        $usageData->containerId  = $data->course;
        $usageData->resourceId   = $newItemId;
        $utils                   = new UtilityFunctions();
        $usageData->nodeId       = $utils->getObjectIdFromUrl($data->object_url);
        $usageData->nodeVersion  = $data->object_version;
        try {
            return $eduService->createUsage($usageData);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return null;
        }
    }
}
