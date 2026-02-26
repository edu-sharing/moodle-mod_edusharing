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
 * Index for edu-sharing plugin
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_edusharing\event\course_module_instance_list_viewed;


global $DB, $PAGE, $OUTPUT, $CFG;

require_once(dirname(__FILE__, 3) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

try {
    $id = required_param('id', PARAM_INT);
    if (!$course = $DB->get_record('course', ['id' => $id])) {
        trigger_error(get_string('error_load_course', 'edusharing'), E_USER_WARNING);
    }
    require_course_login($course);
    $event = course_module_instance_list_viewed::create([
        'context' => context_course::instance($course->id),
    ]);
    $event->trigger();
    $PAGE->set_url('mod/edusharing/view.php', ['id' => $id]);
    $PAGE->set_title($course->fullname);
    $PAGE->set_heading($course->shortname);

    echo $OUTPUT->header();

    if (!$edusharings = get_all_instances_in_course('edusharing', $course)) {
        echo $OUTPUT->heading(get_string('noedusharings', 'edusharing'), 2);
        echo $OUTPUT->continue_button("view.php?id=$course->id");
        echo $OUTPUT->footer();
        die();
    }


    $timenow  = time();
    $strname  = get_string('name');
    $strweek  = get_string('week');
    $strtopic = get_string('topic');
    $table    = new html_table();
    if ($course->format == 'weeks') {
        $table->head  = [$strweek, $strname];
        $table->align = ['center', 'left'];
    } else if ($course->format == 'topics') {
        $table->head  = [$strtopic, $strname];
        $table->align = ['center', 'left', 'left', 'left'];
    } else {
        $table->head  = [$strname];
        $table->align = ['left', 'left', 'left'];
    }
    foreach ($edusharings as $edusharing) {
        if (!$edusharing->visible) {
            // Show dimmed if the mod is hidden.
            $link = '<a class="dimmed" href="view.php?id='
                . $edusharing->coursemodule . '">' . format_string($edusharing->name) . '</a>';
        } else {
            // Show normal if the mod is visible.
            $link = '<a href="view.php?id=' . $edusharing->coursemodule . '">' . format_string($edusharing->name) . '</a>';
        }

        if ($course->format == 'weeks' || $course->format == 'topics') {
            $table->data[] = [$edusharing->section, $link];
        } else {
            $table->data[] = [$link];
        }
    }

    echo $OUTPUT->heading(get_string('modulenameplural', 'mod_edusharing'), 2);
} catch (Exception $exception) {
    debugging($exception->getLine() . ': ' . $exception->getMessage());
    unset($exception);
    echo('error');
    die();
}

echo html_writer::table($table);
echo $OUTPUT->footer();
