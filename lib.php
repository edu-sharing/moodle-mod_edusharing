<?php
// This file is part of Moodle - http://moodle.org/
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants for module edusharing
 *
 * All the core Moodle functions, needed to allow the module to work
 * integrated in Moodle should be placed here.
 * All the edusharing specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

use mod_edusharing\EduSharingService;
use mod_edusharing\UtilityFunctions;

defined('MOODLE_INTERNAL') || die();

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/lib');

/**
 * Module feature detection.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return int|bool True if module supports feature, false if not, null if doesn't know
 */
function edusharing_supports(string $feature): int|bool {
    if (defined('FEATURE_CAN_DISPLAY') && $feature === FEATURE_CAN_DISPLAY) {
        return true;
    }

    return match ($feature) {
            FEATURE_MOD_ARCHETYPE => MOD_ARCHETYPE_RESOURCE,
            FEATURE_MOD_INTRO, FEATURE_SHOW_DESCRIPTION, FEATURE_BACKUP_MOODLE2 => true,
            default => false,
    };
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $edusharing An object from the form in mod_form.php
 * @return int|bool The id of the newly inserted edusharing record
 */
function edusharing_add_instance(stdClass $edusharing): int|bool {
    $service = new EduSharingService();
    try {
        $id = $service->add_instance($edusharing);
    } catch (Exception $exception) {
        debugging('Instance creation failed: ' . $exception->getMessage());
        return false;
    }
    return $id;
}

/**
 * Function edusharing_update_instance
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $edusharing An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function edusharing_update_instance(stdClass $edusharing): bool {
    $service = new EduSharingService();
    try {
        $service->update_instance($edusharing);
    } catch (Exception $exception) {
        debugging('Instance update failed: ' . $exception->getMessage());
        return false;
    }
    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function edusharing_delete_instance($id): bool {
    $service = new EduSharingService();
    try {
        $service->delete_instance((string)$id);
    } catch (Exception $exception) {
        debugging('Instance deletion failed: ' . $exception->getMessage());
        return false;
    }
    return true;
}


/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $edusharing
 *
 * @return stdClass
 */
function edusharing_user_outline($course, $user, $mod, $edusharing) {
    $return       = new stdClass;
    $return->time = time();
    $return->info = 'edusharing_user_outline() - edu-sharing activity outline.';

    return $return;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $edusharing
 *
 * @return boolean
 */
function edusharing_user_complete($course, $user, $mod, $edusharing) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in edusharing activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param object $course
 * @param object $isteacher
 * @param object $timestart
 *
 * @return boolean
 */
function edusharing_print_recent_activity($course, $isteacher, $timestart) {
    return false;
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 **/
function edusharing_cron() {
    return true;
}

/**
 * Must return an array of users who are participants for a given instance
 * of edusharing. Must include every user involved in the instance,
 * independient of his role (student, teacher, admin...). The returned
 * objects must contain at least id property.
 * See other modules as example.
 *
 * @param int $edusharingid ID of an instance of this module
 * @return boolean|array false if no participants, array of objects otherwise
 */
function edusharing_get_participants($edusharingid) {
    return false;
}

/**
 * This function returns if a scale is being used by one edusharing
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $edusharingid ID of an instance of this module
 * @param int $scaleid
 * @return mixed
 */
function edusharing_scale_used($edusharingid, $scaleid) {
    return false;
}

/**
 * Checks if scale is being used by any instance of edusharing.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param int $scaleid
 * @return boolean True if the scale is used by any edusharing
 */
function edusharing_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Execute post-install actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function edusharing_install() {
    return true;
}

/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function edusharing_uninstall() {
    return true;
}

/**
 * Moodle will cache the outpu of this method, so it gets only called after
 * adding or updating an edu-sharing-resource, NOT every time the course
 * is shown.
 *
 * @param stdClass $coursemodule
 * @return stdClass|bool
 */
function edusharing_get_coursemodule_info(stdClass $coursemodule): cached_cm_info|bool {
    $utils = new UtilityFunctions();
    return $utils->get_course_module_info($coursemodule);
}

/**
 * Hook called before we delete a course module.
 *
 * @param \stdClass $cm The course module record.
 */
function edusharing_pre_course_module_delete($cm) {
    return false;
}

/**
 * Function edusharing_course_module_background_deletion_recommended
 *
 * @return false
 */
function edusharing_course_module_background_deletion_recommended() {
    return false;
}


/**
 * Function edusharing_pre_block_delete
 *
 * @param mixed $cm
 * @return false
 */
function edusharing_pre_block_delete($cm) {
    return false;
}

/**
 * Function edusharing_update_settings_images
 *
 * @param string $settingname
 * @return void
 */
function edusharing_update_settings_images(string $settingname) {
    $utils = new UtilityFunctions();
    $utils->update_settings_images($settingname);
}

/**
 * Function edusharing_update_settings_name
 *
 * @return void
 */
function edusharing_update_settings_name() {
    // Reset language cache.
    get_string_manager()->reset_caches();
}
