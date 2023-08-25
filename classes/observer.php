<?php declare(strict_types=1);

use mod_edusharing\EduSharingService;
use mod_edusharing\RestoreHelper;
use mod_edusharing\UtilityFunctions;


defined('MOODLE_INTERNAL') || die();

/**
 * Class mod_edusharing_observer
 *
 * callback definitions for events.
 */
class mod_edusharing_observer
{
    /**
     * Function courseModuleDeleted
     *
     * @param \core\event\course_module_deleted $event
     * @return void
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event) {
        global $DB;
        $data     = $event->get_data();
        $objectId = $data['objectid'];
        //delete es-activities in course-modules
        try {
            $eduObjects = $DB->get_records('edusharing', ['module_id' => $objectId]);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return;
        }
        $service = new EduSharingService();
        foreach ($eduObjects as $object) {
            try {
                $service->deleteInstance($object['id']);
            } catch (Exception $exception) {
                error_log($exception->getMessage());
            }
        }
        //delete es-activities in course-sections
        try {
            $eduObjects = $DB->get_records('edusharing', ['section_id' => $objectId]);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return;
        }
        foreach ($eduObjects as $object) {
            try {
                $service->deleteInstance($object['id']);
            } catch (Exception $exception) {
                error_log($exception->getMessage());
            }
        }
    }

    /**
     * Function course_module_created
     */
    public static function course_module_created(\core\event\course_module_created $event) {
        global $DB;
        $data = $event->get_data();
        try {
            $module = $DB->get_record($data['other']['modulename'], ['id' => $data['other']['instanceid']], '*', MUST_EXIST);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return;
        }
        $text   = $module->intro;
        $idType = 'module_id';
        $utils  = new UtilityFunctions();
        $utils->setModuleIdInDb($text, $data, $idType);
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
            error_log($exception->getMessage());
            return;
        }
        $text   = $module->intro;
        $idType = 'module_id';
        $utils  = new UtilityFunctions();
        $utils->setModuleIdInDb($text, $data, $idType);
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
            error_log($exception->getMessage());
            return;
        }
        $text   = $module->summary;
        $idType = 'section_id';
        $utils  = new UtilityFunctions();
        $utils->setModuleIdInDb($text, $data, $idType);
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
            error_log($exception->getMessage());
            return;
        }
        $text   = $module->summary;
        $idType = 'section_id';
        $utils  = new UtilityFunctions();
        $utils->setModuleIdInDb($text, $data, $idType);;
    }


    /**
     * Function courseDeleted
     *
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB;
        $data     = $event->get_data();
        $objectId = $data['objectid'];
        try {
            $eduObjects = $DB->get_records('edusharing', ['course' => $objectId], '*', MUST_EXIST);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return;
        }
        $service = new EduSharingService();
        foreach ($eduObjects as $object) {
            try {
                $service->deleteInstance($object['id']);
            } catch (Exception $exception) {
                error_log($exception->getMessage());
            }
        }
    }

    /**
     * Function courseRestored
     *
     * @param \core\event\course_restored $event
     */
    public static function courseRestored(\core\event\course_restored $event) {
        $eventData = $event->get_data();
        $courseId  = $eventData['courseid'];
        try {
            $helper = new RestoreHelper(new EduSharingService());
            $helper->convertInlineOptions($courseId);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
        }
    }
}
