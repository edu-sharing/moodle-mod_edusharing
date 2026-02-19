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
 * Structure step to restore one edusharing activity
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_edusharing\EduSharingService;
use mod_edusharing\RestoreHelper;

require_once(dirname(__FILE__) . '/../../lib.php');

/**
 * class restore_edusharing_activity_structure_step
 */
class restore_edusharing_activity_structure_step extends restore_activity_structure_step {
    /**
     * Function define_structure
     *
     * @return mixed
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('edusharing', '/activity/edusharing');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Function process_edusharing
     *
     * @param object $data
     * @return void
     */
    protected function process_edusharing($data) {
        global $DB;

        $data = (object)$data;
        try {
            $data->course = $this->get_courseid();
            // Insert the edusharing record.
            $newid = $DB->insert_record('edusharing', $data);
            // Immediately after inserting "activity" record, call this.
            $helper = new RestoreHelper(new EduSharingService());
            $helper->add_usage($data, $newid, $this->get_task()->get_userid());
            $this->apply_activity_instance($newid);
        } catch (Exception $exception) {
            try {
                isset($newid) && $DB->delete_records('edusharing', ['id' => $newid]);
                $message = str_contains($exception->getMessage(), 'NO_CCPUBLISH_PERMISSION')
                    ? get_string('exc_NO_PUBLISH_RIGHTS', 'edusharing') : $exception->getMessage();
                $this->log($message, backup::LOG_ERROR, null, null, true);
            } catch (Exception $stupidexception) {
                // Well, there is only so much we can do...
                unset($stupidexception);
            }
        }
    }

    /**
     * Function after_execute
     *
     * @return void
     */
    protected function after_execute() {
    }
}
