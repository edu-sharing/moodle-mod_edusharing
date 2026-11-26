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

namespace mod_edusharing\task;

use core\task\adhoc_task;
use mod_edusharing\grading\Grader;

/**
 * Regrades the activities graded from H5P attempts
 *
 * Queued by the upgrade that repairs the attempts' scaled scores (db/upgradelib.php): Moodle
 * does not write grades while an upgrade runs. Grades overridden or locked in the gradebook are
 * left as they are, as on any regrade.
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class regrade_attempts extends adhoc_task {
    /**
     * Regrades every activity graded out of points from attempts that has attempts.
     *
     * @return void
     */
    public function execute(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/edusharing/lib.php');

        // Manual grading takes no grades from attempts; its regrade would remove the automatic ones.
        $instances = $DB->get_recordset_sql(
            "SELECT e.*, cm.id AS cmid
               FROM {edusharing} e
               JOIN {modules} m ON m.name = 'edusharing'
               JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = e.id
              WHERE e.grade > 0 AND e.grade_method <> :manual
                    AND EXISTS (SELECT 1 FROM {edusharing_attempts} a WHERE a.edusharingid = e.id)",
            ['manual' => Grader::GRADEMANUAL]
        );
        foreach ($instances as $instance) {
            mtrace("Regrading edu-sharing activity {$instance->id} (course module {$instance->cmid})");
            edusharing_update_grades($instance);
        }
        $instances->close();
    }
}
