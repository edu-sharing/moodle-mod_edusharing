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

// phpcs:disable moodle.Files.RequireLogin.Missing

require_once(dirname(__FILE__, 4) . '/config.php');
global $CFG;
require_once($CFG->dirroot . '/user/profile/lib.php');

use mod_edusharing\EduSharingService;
use mod_edusharing\IdpHelper;
use mod_edusharing\RestoreHelper;
use mod_edusharing\UtilityFunctions;

/**
 * Class mod_edusharing_observer
 *
 * callback definitions for events.
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_edusharing_observer {
    /**
     * Function course_module_deleted
     *
     * @param \core\event\course_module_deleted $event
     * @return void
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event) {
        global $DB;
        $data     = $event->get_data();
        $objectid = $data['objectid'];
        // Delete es-activities in course-modules.
        try {
            $eduobjects = $DB->get_records('edusharing', ['module_id' => $objectid]);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return;
        }
        $service = new EduSharingService();
        foreach ($eduobjects as $object) {
            try {
                $service->delete_instance($object['id']);
            } catch (Exception $exception) {
                debugging($exception->getMessage());
            }
        }
    }

    /**
     * Function course_module_created
     *
     * @param \core\event\course_module_created $event
     */
    public static function course_module_created(\core\event\course_module_created $event) {
        global $DB;
        $data = $event->get_data();
        try {
            $module = $DB->get_record($data['other']['modulename'], ['id' => $data['other']['instanceid']], '*', MUST_EXIST);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return;
        }
        $text = $module->intro;
        if ($text === null) {
            return;
        }
        $utils         = new UtilityFunctions();
        $backtrace     = debug_backtrace();
        $isduplication = false;
        foreach ($backtrace as $call) {
            if (in_array($call['function'], ['cm_duplicate', 'duplicate_module'], true)) {
                $isduplication = true;
            }
        }
        if ($isduplication) {
            $createdusages = [];
            $matches = $utils->get_inline_object_matches($text)['rendermatches'];
            $transaction = $DB->start_delegated_transaction();
            try {
                $service = new EduSharingService(null, null, $utils);
            } catch (Exception $exception) {
                debugging($exception->getMessage());
                return;
            }
            try {
                foreach ($matches as $match) {
                    $originalresourceid = $utils->get_resource_id_from_match($match);
                    $edusharing = $DB->get_record('edusharing', ['id' => $originalresourceid], '*', MUST_EXIST);
                    unset($edusharing->id);
                    unset($edusharing->usage_id);
                    $edusharing->module_id = $data['objectid'];
                    $newresourceid = $service->add_instance($edusharing);
                    $newresourceid === false && throw new Exception('ES add instance failed');
                    if (isset($edusharing->usage_id)) {
                        $currentusage = new stdClass();
                        $currentusage->nodeId = $utils->get_object_id_from_url($edusharing->object_url);
                        $currentusage->usageId = $edusharing->usage_id;
                        $createdusages[] = $currentusage;
                    }
                    $text = str_replace("resourceId=$originalresourceid", "resourceId=$newresourceid", $text);
                }
                $DB->set_field($data['other']['modulename'], 'intro', $text, ['id' => $data['other']['instanceid']]);
                $transaction->allow_commit();
            } catch (Exception $exception) {
                try {
                    foreach ($createdusages as $usagedata) {
                        $service->delete_usage($usagedata);
                    }
                    $DB->rollback_delegated_transaction($transaction, $exception);
                } catch (Throwable $cleanupexception) {
                    debugging($cleanupexception);
                }
            }
            return;
        }
        try {
            $coursemodule = $DB->get_record($data['objecttable'], ['id' => $data['objectid']], '*', MUST_EXIST);
            $utils->set_moodle_ids_in_edusharing_entries($text, (int)$coursemodule->section, (int)$data['objectid']);
        } catch (Exception $exception) {
            debugging($exception);
        }
    }

    /**
     * Function course_module_updated
     *
     * @param \core\event\course_module_updated $event
     * @return void
     */
    public static function course_module_updated(\core\event\course_module_updated $event) {
        global $DB;
        $data = $event->get_data();
        try {
            $module = $DB->get_record($data['other']['modulename'], ['id' => $data['other']['instanceid']], '*', MUST_EXIST);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return;
        }
        $text = $module->intro;
        if ($text === null) {
            return;
        }
        $utils = new UtilityFunctions();
        try {
            $coursemodule = $DB->get_record($data['objecttable'], ['id' => $data['objectid']], '*', MUST_EXIST);
            $utils->set_moodle_ids_in_edusharing_entries($text, (int)$coursemodule->section, (int)$data['objectid']);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
        }
    }

    /**
     * Function course_section_created
     *
     * @param \core\event\course_section_created $event
     * @return void
     */
    public static function course_section_created(\core\event\course_section_created $event) {
        global $DB;
        $data = $event->get_data();
        try {
            $module = $DB->get_record('course_sections', ['id' => $data['objectid']], '*', MUST_EXIST);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return;
        }
        $text = $module->summary;
        if ($text === null) {
            return;
        }
        $utils = new UtilityFunctions();
        try {
            $utils->set_moodle_ids_in_edusharing_entries($text, (int)$data['objectid']);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
        }
    }

    /**
     * Function course_section_updated
     *
     * @param \core\event\course_section_updated $event
     * @return void
     */
    public static function course_section_updated(\core\event\course_section_updated $event) {
        global $DB;
        $data = $event->get_data();
        try {
            $section = $DB->get_record('course_sections', ['id' => $data['objectid']], '*', MUST_EXIST);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return;
        }
        $text = $section->summary;
        if ($text === null) {
            return;
        }
        $utils = new UtilityFunctions();
        try {
            $utils->set_moodle_ids_in_edusharing_entries($text, (int)$data['objectid']);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
        }
    }

    /**
     * Function course_section_deleted
     *
     * @param \core\event\course_section_deleted $event
     * @return void
     */
    public static function course_section_deleted(\core\event\course_section_deleted $event) {
        global $DB;
        $data     = $event->get_data();
        $objectid = $data['objectid'];
        try {
            $eduobjects = $DB->get_records('edusharing', ['section_id' => $objectid]);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return;
        }
        $service = new EduSharingService();
        foreach ($eduobjects as $object) {
            try {
                $service->delete_instance($object->id);
            } catch (Exception $exception) {
                debugging($exception->getMessage());
            }
        }
    }

    /**
     * Function course_deleted
     *
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB;
        $data     = $event->get_data();
        $objectid = $data['objectid'];
        try {
            $eduobjects = $DB->get_records('edusharing', ['course' => $objectid]);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return;
        }
        $service = new EduSharingService();
        foreach ($eduobjects as $object) {
            try {
                $service->delete_instance($object->id);
            } catch (Exception $exception) {
                debugging($exception->getMessage());
            }
        }
    }

    /**
     * Function course_restored
     *
     * @param \core\event\course_restored $event
     */
    public static function course_restored(\core\event\course_restored $event) {
        $userid = empty($event->userid) ? null : (int)$event->userid;
        $eventdata = $event->get_data();
        $courseid  = $eventdata['courseid'];
        try {
            $helper = new RestoreHelper(new EduSharingService());
            $helper->convert_inline_options(courseid: (int)$courseid, userid: $userid);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
        }
    }

    /**
     * Function user_loggedin
     *
     * @param \core\event\user_loggedin $event
     * @throws moodle_exception
     */
    public static function user_loggedin(\core\event\user_loggedin $event) {
        global $SESSION, $USER, $DB;
        $utils = new UtilityFunctions();
        $idphelper = new IdpHelper();

        if (isset($SESSION->redirect_to_edusharing) && $idphelper->check_edu_access()) {
            unset($SESSION->redirect_to_edusharing);
            $service = new EduSharingService();
            $ticket  = $service->get_ticket();
            $repourl = rtrim($utils->get_config_entry('application_cc_gui_url'), '/') . '/components/login?ticket=' . $ticket;
            redirect(new moodle_url($repourl));
        }

        $SESSION->edusharing_sso = [];
        try {
            if ($utils->get_config_entry('obfuscate_auth_param') !== '1') {
                return;
            }
            $authparam = $utils->get_config_entry('EDU_AUTH_PARAM_NAME_USERID');
            $salt = $utils->get_config_entry('SALT');
            if ($salt === false) {
                $salt = uniqid();
                $utils->set_config_entry('SALT', $salt);
            }
            $SESSION->edusharing_sso[$authparam] = hash('sha256', $USER->username . $salt)
                . '@' . $utils->get_config_entry('application_appid');
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return;
        }
    }
}
