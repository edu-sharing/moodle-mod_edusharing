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

use mod_edusharing\EduSharingService;
use mod_edusharing\RestoreHelper;
use mod_edusharing\UtilityFunctions;

/**
 * Class mod_edusharing_observer
 *
 * callback definitions for events.
 * @package mod_edusharing
 */
class mod_edusharing_observer {
    /**
     * Function courseModuleDeleted
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
        // Delete es-activities in course-sections.
        try {
            $eduobjects = $DB->get_records('edusharing', ['section_id' => $objectid]);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return;
        }
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
        $text   = $module->intro;
        if ($text === null) {
            return;
        }
        $idtype = 'module_id';
        $utils  = new UtilityFunctions();
        $utils->set_module_id_in_db($text, $data, $idtype);
    }

    /**
     * Function courseModuleCreatedOrUpdated
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
        $text   = $module->intro;
        if ($text === null) {
            return;
        }
        $idtype = 'module_id';
        $utils  = new UtilityFunctions();
        $utils->set_module_id_in_db($text, $data, $idtype);
    }

    /**
     * Function courseSectionUpdatedOrCreated
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
        $idtype = 'section_id';
        $utils  = new UtilityFunctions();
        $utils->set_module_id_in_db($text, $data, $idtype);
    }

    /**
     * Function courseSectionUpdatedOrCreated
     *
     * @param \core\event\course_section_updated $event
     * @return void
     */
    public static function course_section_updated(\core\event\course_section_updated $event) {
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
        $idtype = 'section_id';
        $utils  = new UtilityFunctions();
        $utils->set_module_id_in_db($text, $data, $idtype);;
    }


    /**
     * Function courseDeleted
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
        $eventdata = $event->get_data();
        $courseid  = $eventdata['courseid'];
        try {
            $helper = new RestoreHelper(new EduSharingService());
            $helper->convert_inline_options($courseid);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
        }
    }

    /**
     * Function user_loggedin
     *
     * @param \core\event\user_loggedin $event
     */
    public static function user_loggedin(\core\event\user_loggedin $event) {
        global $SESSION, $USER;
        $SESSION->edusharing_sso = [];
        $utils = new UtilityFunctions();
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
