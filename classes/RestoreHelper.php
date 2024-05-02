<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

declare(strict_types=1);

namespace mod_edusharing;

use coding_exception;
use dml_exception;
use DOMDocument;
use EduSharingApiClient\MissingRightsException;
use EduSharingApiClient\Usage;
use Exception;
use moodle_exception;
use stdClass;

/**
 * Class RestoreHelper
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 */
class RestoreHelper {
    /**
     * @var EduSharingService
     */
    private EduSharingService $service;

    /**
     * RestoreHelper constructor
     *
     * @param EduSharingService $service
     */
    public function __construct(EduSharingService $service) {
        $this->service = $service;
    }

    /**
     * Function convert_inline_options
     *
     * @param int $courseid
     * @return void
     *
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function convert_inline_options($courseid): void {
        global $DB;
        $sections = $DB->get_records('course_sections', ['course' => $courseid]);
        foreach ($sections as $section) {
            $matchesatto = $this->get_inline_objects($section->summary ?? '');
            if (!empty($matchesatto)) {
                foreach ($matchesatto as $match) {
                    $section->summary = str_replace($match, $this->convert_object($match, $courseid), $section->summary);
                    $DB->update_record('course_sections', $section);
                }
            }
        }
        $modules = get_course_mods($courseid);
        $course  = get_course($courseid);
        foreach ($modules as $module) {
            $modinfo = get_fast_modinfo($course);
            $cm      = $modinfo->get_cm($module->id);
            if (!empty($cm->content)) {
                $matchesatto = $this->get_inline_objects($cm->content);
                if (!empty($matchesatto)) {
                    foreach ($matchesatto as $match) {
                        $cm->set_content(str_replace($match, $this->convert_object($match, $courseid), $cm->content));
                    }
                }
            }
            try {
                $module = $DB->get_record($cm->name, ['id' => $cm->instance], '*', MUST_EXIST);
            } catch (Exception $exception) {
                debugging($exception->getMessage());
                continue;
            }
            if (!empty($module->intro)) {
                $matchesatto = $this->get_inline_objects($module->intro);
                if (!empty($matchesatto)) {
                    foreach ($matchesatto as $match) {
                        $module->intro = str_replace($match, $this->convert_object($match, $courseid), $module->intro);
                    }
                }
            }
            $DB->update_record($cm->name, $module);
        }
        rebuild_course_cache((int)$courseid, true);
    }

    /**
     * Function get_inline_objects
     *
     * @param string $text
     * @return array
     */
    private function get_inline_objects(string $text): array {
        if (!str_contains($text, 'edusharing_atto')) {
            return [];
        }
        if (isloggedin()) {
            try {
                $this->service->get_ticket();
            } catch (Exception $exception) {
                trigger_error($exception->getMessage(), E_USER_WARNING);
                return [];
            }
        }
        preg_match_all('#<img(.*)class="(.*)edusharing_atto(.*)"(.*)>#Umsi', $text, $matchesimgatto, PREG_PATTERN_ORDER);
        preg_match_all('#<a(.*)class="(.*)edusharing_atto(.*)">(.*)</a>#Umsi', $text, $matchesaatto, PREG_PATTERN_ORDER);
        return array_merge($matchesimgatto[0], $matchesaatto[0]);
    }

    /**
     * Function convert_object
     *
     * @param mixed $object
     * @param mixed $courseid
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     * @throws Exception
     */
    private function convert_object($object, $courseid): string {
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
        $params = [];
        parse_str(parse_url($qs, PHP_URL_QUERY), $params);
        $edusharing                 = new stdClass();
        $edusharing->course         = $courseid;
        $edusharing->name           = $params['title'];
        $edusharing->introformat    = 0;
        $edusharing->object_url     = $params['object_url'];
        $edusharing->object_version = $params['window_version'];
        $edusharing->timecreated    = time();
        $edusharing->timemodified   = time();
        $id                         = $DB->insert_record('edusharing', $edusharing);
        if ($id !== false) {
            try {
                $usage = $this->add_usage($edusharing, $id);
            } catch (MissingRightsException $missingrightsexception) {
                unset($missingrightsexception);
                return get_string('error_missing_rights_on_restore' . ': ' . ($params['nodeId'] ?? 'blank nodeId'));
            } catch (Exception $exception) {
                unset($exception);
                return '';
            }
            if ($usage !== null) {
                if (isset($usage->usageId)) {
                    $edusharing->id      = $id;
                    $edusharing->usageId = $usage->usageId;
                    $DB->update_record(Constants::EDUSHARING_TABLE, $edusharing);
                }
                $params['resourceId'] = $id;
                $url                  = strtok($qs, '?') . '?';
                foreach ($params as $paramn => $paramv) {
                    $url .= $paramn . '=' . $paramv . '&';
                }
                $node->setAttribute($type === 'a' ? 'href' : 'src', $url);
            } else {
                $DB->delete_records('edusharing', ['id' => $id]);
                return $object;
            }
        }
        return $doc->saveHTML();
    }

    /**
     * Function add_usage
     *
     * @param stdClass $data
     * @param int $newitemid
     * @return Usage|null
     * @throws \JsonException
     * @throws Exception
     * @throws MissingRightsException
     */
    public function add_usage(stdClass $data, int $newitemid): ?Usage {
        $usagedata              = new stdClass();
        $usagedata->containerId = $data->course;
        $usagedata->resourceId  = $newitemid;
        $utils                  = new UtilityFunctions();
        $usagedata->nodeId      = $utils->get_object_id_from_url($data->object_url);
        $usagedata->nodeVersion = $data->object_version;
        return $this->service->create_usage($usagedata);
    }
}
