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
use core_user;
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
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class RestoreHelper {
    /**
     * @var EduSharingService
     */
    private EduSharingService $service;

    /**
     * @var UtilityFunctions
     */
    private UtilityFunctions $utils;

    /**
     * RestoreHelper constructor
     *
     * @param EduSharingService $service
     */
    public function __construct(EduSharingService $service) {
        $this->service = $service;
        $this->init();
    }

    /**
     * Function init
     *
     * @return void
     */
    private function init(): void {
        $this->utils = new UtilityFunctions();
    }

    /**
     * Function convert_inline_options
     *
     * @param int $courseid
     * @param int|null $userid
     * @return void
     *
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function convert_inline_options(int $courseid, ?int $userid = null): void {
        global $DB;
        $sections = $DB->get_records('course_sections', ['course' => $courseid]);
        foreach ($sections as $section) {
            $esmatches = $this->get_inline_objects($section->summary ?? '');
            if (empty($esmatches)) {
                continue;
            }
            foreach ($esmatches as $match) {
                $section->summary = $this->replace_object(
                    $section->summary,
                    $match,
                    $this->convert_object($match, $courseid, $userid, sectionid: (int)$section->id)
                );
            }
            $DB->update_record('course_sections', $section);
        }
        $course  = get_course($courseid);
        $modinfo = get_fast_modinfo($course);
        foreach (get_course_mods($courseid) as $coursemodule) {
            $cm = $modinfo->get_cm($coursemodule->id);
            // Note: cm_info::$modname is the module type, and therefore the name of its main
            // database table - cm_info::$name is the instance name shown on the course page.
            try {
                $instance = $DB->get_record($cm->modname, ['id' => $cm->instance], '*', MUST_EXIST);
            } catch (Exception $exception) {
                mtrace($exception->getMessage());
                continue;
            }
            $isconverted = false;
            foreach ($this->utils->get_text_fields($instance) as $field => $text) {
                $esmatches = $this->get_inline_objects($text);
                if (empty($esmatches)) {
                    continue;
                }
                foreach ($esmatches as $match) {
                    $text = $this->replace_object(
                        $text,
                        $match,
                        $this->convert_object($match, $courseid, $userid, moduleid: (int)$cm->id)
                    );
                }
                $instance->$field = $text;
                $isconverted      = true;
            }
            if ($isconverted) {
                $DB->update_record($cm->modname, $instance);
            }
        }
        rebuild_course_cache($courseid, true);
    }

    /**
     * Function replace_object
     *
     * Replaces an object in a text by its conversion. Tiny wraps each object in a non-editable placeholder div
     * (with the caption in it, if any). If the conversion is no object anymore, be it '' for a stripped object or an
     * error message, the wrapper is replaced as well, as it would be left behind as an invisible block. A converted
     * object keeps its wrapper.
     *
     * @param string $text
     * @param string $match the html of the object element
     * @param string $converted
     * @return string
     */
    private function replace_object(string $text, string $match, string $converted): string {
        if (!str_contains($converted, 'edusharing_atto')) {
            $wrapper = $this->find_wrapper($text, $match);
            if ($wrapper !== null) {
                return str_replace($wrapper, $converted, $text);
            }
        }
        return str_replace($match, $converted, $text);
    }

    /**
     * Function find_wrapper
     *
     * @param string $text
     * @param string $match the html of the object element
     * @return string|null the html of the object's Tiny wrapper, null if it has none or is not found in the text as is
     */
    private function find_wrapper(string $text, string $match): ?string {
        foreach ($this->utils->get_elements_by_class($text, 'edusharing-placeholder') as $wrapper) {
            // A wrapper holding a second object must stay, it would take the converted sibling with it.
            if (
                str_contains($wrapper, $match)
                && count($this->utils->get_elements_by_class($wrapper, 'edusharing_atto')) === 1
            ) {
                return $wrapper;
            }
        }
        return null;
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

        return $this->utils->get_inline_object_matches($text)['rendermatches'];
    }

    /**
     * Function load_inline_object
     *
     * Objects the Tiny editor created from a pasted edu-sharing embed iframe carry only resourceId, nodeId, mimetype,
     * mediatype, width and height in their url. Title, object url and version are missing there, but the restore
     * needs them to store the object. So the title is taken from the element, the object url is built from the node id
     * and this site's home repository, as the editor does, and the version falls back to '0', which means the latest.
     * A version the object was pinned to cannot be recovered this way. Regular embeds carry all three and are left
     * untouched. Backups made by this version already carry the values (see BackupHelper::complete_inline_objects),
     * so the fallback is only for older backups and for objects whose record was not found at backup time.
     *
     * @param string $object the html of an embedded edu-sharing object
     * @return array|null ['doc' => DOMDocument, 'node' => DOMElement, 'type' => 'a'|'img', 'params' => array]
     */
    private static function load_inline_object(string $object): ?array {
        $parsed = self::parse_inline_object($object);
        if ($parsed === null) {
            return null;
        }
        $node   = $parsed['node'];
        $params = $parsed['params'];
        $params['title'] ??= $node->getAttribute('title') ?: $node->getAttribute('alt') ?: (string)($params['nodeId'] ?? '');
        $homerepid = (string)get_config('edusharing', 'application_homerepid');
        if (!isset($params['object_url']) && !empty($params['nodeId']) && $homerepid !== '') {
            $params['object_url'] = 'ccrep://' . rawurlencode($homerepid) . '/' . rawurlencode((string)$params['nodeId']);
        }
        $params['window_version'] ??= '0';
        $parsed['params'] = $params;
        return $parsed;
    }

    /**
     * Function parse_inline_object
     *
     * Parses the html of an embedded edu-sharing object and returns the raw query params of its url, without any
     * fallback for missing ones.
     *
     * @param string $object the html of an embedded edu-sharing object
     * @return array|null ['doc' => DOMDocument, 'node' => DOMElement, 'type' => 'a'|'img', 'params' => array]
     */
    public static function parse_inline_object(string $object): ?array {
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $doc = new DOMDocument();
        $doc->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $object,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        $errors = libxml_get_errors();
        libxml_clear_errors();
        if (!empty($errors)) {
            debugging("Html parsing error(s): " . json_encode($errors));
            debugging($object);
            return null;
        }
        $type = 'a';
        $node = $doc->getElementsByTagName('a')->item(0);
        if (empty($node)) {
            $type = 'img';
            $node = $doc->getElementsByTagName('img')->item(0);
        }
        if (empty($node)) {
            debugging("ES object element could not be found.");
            debugging($object);
            return null;
        }
        $queryparams = parse_url($node->getAttribute($type === 'a' ? 'href' : 'src'), PHP_URL_QUERY);
        if (empty($queryparams)) {
            debugging("ES object url could not be retrieved or parsed.");
            debugging($object);
            return null;
        }
        $params = [];
        parse_str($queryparams, $params);
        return ['doc' => $doc, 'node' => $node, 'type' => $type, 'params' => $params];
    }

    /**
     * Function get_inline_object_params
     *
     * @param string $object the html of an embedded edu-sharing object
     * @return array|null the query params of the object's url, null if it cannot be parsed
     */
    public static function get_inline_object_params(string $object): ?array {
        return self::load_inline_object($object)['params'] ?? null;
    }

    /**
     * Function get_inline_object_nodeid
     *
     * @param array $params the query params of an embedded object's url
     * @return string
     */
    public static function get_inline_object_nodeid(array $params): string {
        $nodeid = (new UtilityFunctions())->get_object_id_from_url($params['object_url'] ?? null);
        return $nodeid !== '' ? $nodeid : (string)($params['nodeId'] ?? '');
    }

    /**
     * Function convert_object
     *
     * Objects the user lacks publish rights for are stripped, which the restore
     * already announced (see local\hook_callbacks::after_restore_root_define_settings)
     *
     * @param mixed $object
     * @param mixed $courseid
     * @param int|null $userid
     * @param int|null $moduleid Course module the object sits in, if any.
     * @param int|null $sectionid Course section the object sits in, if any.
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     */
    private function convert_object(
        $object,
        $courseid,
        ?int $userid,
        ?int $moduleid = null,
        ?int $sectionid = null
    ): string {
        global $CFG, $DB;
        $loaded = self::load_inline_object($object);
        if ($loaded === null) {
            return get_string('error_parsing_on_restore', 'edusharing');
        }
        ['doc' => $doc, 'node' => $node, 'type' => $type, 'params' => $params] = $loaded;
        $checker = new RestoreRightsChecker($this->service, $this->utils);
        if (!$checker->can_publish(self::get_inline_object_nodeid($params), $userid)) {
            return '';
        }
        $edusharing                 = new stdClass();
        $edusharing->course         = $courseid;
        $edusharing->module_id      = $moduleid;
        $edusharing->section_id     = $sectionid;
        $edusharing->name           = $params['title'];
        $edusharing->introformat    = 0;
        $edusharing->object_url     = $params['object_url'] ?? null;
        $edusharing->object_version = $params['window_version'];
        $edusharing->timecreated    = time();
        $edusharing->timemodified   = time();
        try {
            $id = $DB->insert_record('edusharing', $edusharing);
        } catch (dml_exception $exception) {
            debugging($exception->getMessage());
            return get_string('error_unexpected_on_restore', 'edusharing') . ': ' . ($params['nodeId'] ?? 'blank nodeId');
        }
        if ($id !== false) {
            try {
                $usage = $this->add_usage($edusharing, $id, $userid);
            } catch (MissingRightsException $missingrightsexception) {
                unset($missingrightsexception);
                $DB->delete_records('edusharing', ['id' => $id]);
                return '';
            } catch (Exception $exception) {
                unset($exception);
                $DB->delete_records('edusharing', ['id' => $id]);
                return get_string('error_unexpected_on_restore', 'edusharing')  . ': ' . ($params['nodeId'] ?? 'blank nodeId');
            }
            if ($usage !== null) {
                if (isset($usage->usageId)) {
                    $edusharing->id       = $id;
                    $edusharing->usage_id = $usage->usageId;
                    $DB->update_record(Constants::EDUSHARING_TABLE, $edusharing);
                }
                $params['resourceId'] = $id;
                unset($params['ticket']);
                $url = $CFG->wwwroot . '/mod/edusharing/preview.php?' . http_build_query($params, '', '&');
                $node->setAttribute($type === 'a' ? 'href' : 'src', $url);
            } else {
                $DB->delete_records('edusharing', ['id' => $id]);
                return $object;
            }
        }
        return $doc->saveHTML($node);
    }

    /**
     * Function add_usage
     *
     * @param stdClass $data
     * @param int $newitemid
     * @param int|null $userid
     * @return Usage
     * @throws \JsonException
     * @throws Exception
     * @throws MissingRightsException
     */
    public function add_usage(stdClass $data, int $newitemid, ?int $userid = null): Usage {
        $usagedata              = new stdClass();
        $usagedata->containerId = $data->course;
        $usagedata->resourceId  = $newitemid;
        $utils                  = new UtilityFunctions();
        $usagedata->nodeId      = $utils->get_object_id_from_url($data->object_url);
        $usagedata->nodeVersion = $data->object_version;
        if ($userid !== null) {
            $user = core_user::get_user($userid);
            profile_load_custom_fields($user);
            $ticket = $this->service->get_ticket_for_user($user);
            $usagedata->ticket = $ticket;
        }
        return $this->service->create_usage($usagedata);
    }
}
