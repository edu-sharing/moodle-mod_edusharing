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
 * Define all the backup steps that will be used by the backup_choice_activity_task
 *
 * Also: Define the complete choice structure for backup, with file and id annotations
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_edusharing_activity_structure_step extends backup_activity_structure_step {

    /**
     * Function define_structure
     *
     * @return backup_nested_element
     * @throws base_element_struct_exception
     * @throws base_step_exception
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $edusharing = new backup_nested_element('edusharing', ['id'], [
            'course', 'name', 'intro', 'introformat',
            'timecreated', 'timemodified', 'object_url', 'object_version', 'force_download', 'popup_window',
            'show_course_blocks', 'show_directory_links', 'show_menu_bar', 'show_location_bar', 'show_tool_bar',
            'show_status_bar', 'window_allow_resize', 'window_allow_scroll', 'window_width', 'window_height',
            'window_float', ]);

        $edusharing->set_source_table('edusharing', ['id' => backup::VAR_ACTIVITYID]);

        // Build the tree.

        // Define sources.

        // Define id annotations.

        // Define file annotations.

        // Return the root element (choice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($edusharing);

    }
}
