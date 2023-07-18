<?php declare(strict_types = 1);

namespace mod_edusharing;

use core\event\base;
use core\event\course_deleted;
use core\event\course_module_deleted;
use core\event\course_restored;
use Exception;
use mod_edusharing\apiService\EduSharingService;


defined('MOODLE_INTERNAL') || die();

/**
 * Class EdusharingObserver
 *
 * callback definitions for events.
 */
class EdusharingObserver {

    /**
     * Function courseModuleDeleted
     *
     * @param course_module_deleted $event
     * @return void
     */
    public static function courseModuleDeleted(course_module_deleted $event): void {
        global $DB;
        $data     = $event->get_data();
        $objectId = $data['objectid'];
        //delete es-activities in course-modules
        try {
            $eduObjects = $DB -> get_records('edusharing', array('module_id' => $objectId));
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return;
        }
        $service = new EduSharingService();
        foreach($eduObjects as $object) {
            try {
                $service->deleteInstance($object['id']);
            } catch (Exception $exception) {
                error_log($exception->getMessage());
            }
        }
        //delete es-activities in course-sections
        try {
            $eduObjects = $DB -> get_records('edusharing', array('section_id' => $objectId));
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return;
        }
        foreach($eduObjects as $object) {
            try {
                $service->deleteInstance($object['id']);
            } catch (Exception $exception) {
                error_log($exception->getMessage());
            }
        }
    }

    /**
     * Function courseModuleCreatedOrUpdated
     *
     * @param base $event
     * @return void
     */
    public static function courseModuleCreatedOrUpdated(base $event): void {
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
        UtilityFunctions::setModuleIdInDb($text, $data, $idType);
    }

    /**
     * Function courseSectionUpdatedOrCreated
     *
     * @param base $event
     * @return void
     */
    public static function courseSectionUpdatedOrCreated(base $event): void {
        global $DB;
        $data = $event->get_data();
        try {
            $module = $DB->get_record('course_sections', ['id' => $data['objectid']], '*', MUST_EXIST);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return;
        }
        $text    = $module->summary;
        $id_type = 'section_id';
        UtilityFunctions::setModuleIdInDb($text, $data, $id_type);

    }


    /**
     * Function courseDeleted
     *
     * @param course_deleted $event
     * @return void
     */
    public static function courseDeleted(course_deleted $event): void {
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
        foreach($eduObjects as $object) {
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
     * @param course_restored $event
     * @return void
     */
    public static function courseRestored(course_restored $event): void {
        $eventData = $event->get_data();
        $courseId  = $eventData['courseid'];
        try {
            RestoreHelper::convertInlineOptions($courseId);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
        }
    }
}
