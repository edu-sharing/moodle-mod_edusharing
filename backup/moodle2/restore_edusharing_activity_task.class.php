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

/**
 * restore_edusharing_activity_task
 *
 * edusharing restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/edusharing/backup/moodle2/restore_edusharing_stepslib.php');

/**
 * class restore_edusharing_activity_task
 */
class restore_edusharing_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Edusharing only has one structure step.
        $this->add_step(new restore_edusharing_activity_structure_step('edusharing_structure', 'edusharing.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('edusharing', ['intro'], 'edusharing');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('EDUSHARINGVIEWBYID', '/mod/edusharing/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('EDUSHARINGINDEX', '/mod/edusharing/index.php?id=$1', 'course');

        return $rules;
    }

    /**
     * Function define_restore_log_rules
     *
     * Define the restore log rules that will be applied
     *
     */
    public static function define_restore_log_rules() {
        $rules = [];

        $rules[] = new restore_log_rule('edusharing', 'add', 'view.php?id={course_module}', '{edusharing}');
        $rules[] = new restore_log_rule('edusharing', 'update', 'view.php?id={course_module}', '{edusharing}');
        $rules[] = new restore_log_rule('edusharing', 'view', 'view.php?id={course_module}', '{edusharing}');
        $rules[] = new restore_log_rule('edusharing', 'choose', 'view.php?id={course_module}', '{edusharing}');
        $rules[] = new restore_log_rule('edusharing', 'choose again', 'view.php?id={course_module}', '{edusharing}');
        $rules[] = new restore_log_rule('edusharing', 'report', 'report.php?id={course_module}', '{edusharing}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];

        // Fix old wrong uses (missing extension).
        $rules[] = new restore_log_rule('edusharing', 'view all', 'index?id={course}', null,
            null, null, 'index.php?id={course}');
        $rules[] = new restore_log_rule('edusharing', 'view all', 'index.php?id={course}', null);

        return $rules;
    }

    /**
     * Function after_restore
     *
     * @return void
     */
    public function after_restore() {
        // Do something at end of restore.
    }
}
