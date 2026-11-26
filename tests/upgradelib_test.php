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

namespace mod_edusharing;

use mod_edusharing\grading\Grader;
use mod_edusharing\task\regrade_attempts;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use stdClass;
use xmldb_field;
use xmldb_table;

/**
 * Tests for the upgrade helpers in db/upgradelib.php
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversFunction('mod_edusharing_repair_attempt_scores')]
#[CoversClass(regrade_attempts::class)]
final class upgradelib_test extends \advanced_testcase {
    /**
     * Loads the helpers under test.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/db/upgradelib.php');
        require_once($CFG->libdir . '/gradelib.php');
        parent::setUpBeforeClass();
    }

    /**
     * A site that got edusharing_attempts from the upgrade step 2026031603 has scaled without
     * decimals and rounded scores; the repair restores the field, the scores and the grades.
     *
     * @return void
     */
    public function test_repairs_scores_and_grades_of_a_field_without_decimals(): void {
        global $DB;
        $this->resetAfterTest();
        $this->set_scaled_precision('10');
        try {
            $course  = $this->getDataGenerator()->create_course();
            $student = $this->getDataGenerator()->create_user();
            $first   = $this->create_instance($course->id, Grader::GRADEFIRSTATTEMPT);
            $manual  = $this->create_instance($course->id, Grader::GRADEMANUAL);
            // As stored without decimals: 1 of 2 points became 1. The attempt without a maximum
            // keeps its score.
            $this->create_attempt($first, $student->id, 1, 1, 2, 1);
            $this->create_attempt($first, $student->id, 2, 2, 2, 1);
            $this->create_attempt($manual, $student->id, 1, 1, 2, 1);
            $this->create_attempt($manual, $student->id, 2, 0, 0, 0);

            $this->assertTrue(mod_edusharing_repair_attempt_scores());
            // Moodle writes no grades during an upgrade: the regrade is a task.
            $this->assertCount(1, \core\task\manager::get_adhoc_tasks(regrade_attempts::class));
            $this->expectOutputRegex('/Regrading edu-sharing activity ' . $first . ' /');
            $this->runAdhocTasks(regrade_attempts::class);

            $column = $DB->get_columns('edusharing_attempts', false)['scaled'];
            $this->assertEquals([10, 5], [(int)$column->max_length, (int)$column->scale]);
            $scores = $DB->get_records_menu(
                'edusharing_attempts',
                ['edusharingid' => $first, 'userid' => $student->id],
                'attempt',
                'attempt, scaled'
            );
            $this->assertEquals([1 => 0.5, 2 => 1.0], array_map('floatval', $scores));
            $this->assertEquals(0.0, (float)$DB->get_field(
                'edusharing_attempts',
                'scaled',
                ['edusharingid' => $manual, 'attempt' => 2]
            ));

            // The grading method "Erster Versuch": 1 of 2 points of 100.
            $grades = grade_get_grades($course->id, 'mod', 'edusharing', $first, $student->id);
            $this->assertEquals(50.0, (float)$grades->items[0]->grades[$student->id]->grade);
            // Manual grading takes no grade from the attempts.
            $grades = grade_get_grades($course->id, 'mod', 'edusharing', $manual, $student->id);
            $this->assertEmpty($grades->items);
        } finally {
            // Whatever happened above, leave the schema as install.xml defines it.
            $this->set_scaled_precision('10, 5');
        }
    }

    /**
     * A repair interrupted after the field was changed is completed by the next run: the rounded
     * scores are found by their values.
     *
     * @return void
     */
    public function test_completes_an_interrupted_repair(): void {
        global $DB;
        $this->resetAfterTest();
        $course     = $this->getDataGenerator()->create_course();
        $student    = $this->getDataGenerator()->create_user();
        $instanceid = $this->create_instance($course->id, Grader::GRADEFIRSTATTEMPT);
        $this->create_attempt($instanceid, $student->id, 1, 1, 2, 1);

        $this->assertTrue(mod_edusharing_repair_attempt_scores());

        $this->assertEquals(0.5, (float)$DB->get_field('edusharing_attempts', 'scaled', ['edusharingid' => $instanceid]));
        $this->assertCount(1, \core\task\manager::get_adhoc_tasks(regrade_attempts::class));
    }

    /**
     * A site that got the table from install.xml is left as it is.
     *
     * @return void
     */
    public function test_leaves_a_correct_field_alone(): void {
        global $DB;
        $this->resetAfterTest();
        $course     = $this->getDataGenerator()->create_course();
        $student    = $this->getDataGenerator()->create_user();
        $instanceid = $this->create_instance($course->id, Grader::GRADEFIRSTATTEMPT);
        $this->create_attempt($instanceid, $student->id, 1, 1, 3, 0.33333);

        $this->assertFalse(mod_edusharing_repair_attempt_scores());
        $this->assertEmpty(\core\task\manager::get_adhoc_tasks(regrade_attempts::class));

        $this->assertEquals(5, $DB->get_columns('edusharing_attempts', false)['scaled']->scale);
        $this->assertEquals(0.33333, (float)$DB->get_field('edusharing_attempts', 'scaled', ['edusharingid' => $instanceid]));
    }

    /**
     * Sets the precision of edusharing_attempts.scaled, e.g. '10' (no decimals) or '10, 5'.
     *
     * By way of length 11, as the repair does: on PostgreSQL Moodle alters nothing for a change
     * of the decimals alone.
     *
     * @param string $precision
     * @return void
     */
    private function set_scaled_precision(string $precision): void {
        global $DB;
        $decimals = explode(',', $precision)[1] ?? '';
        foreach (['11' . ($decimals === '' ? '' : ',' . $decimals), $precision] as $step) {
            $DB->get_manager()->change_field_precision(
                new xmldb_table('edusharing_attempts'),
                new xmldb_field('scaled', XMLDB_TYPE_NUMBER, $step, null, XMLDB_NOTNULL, null, '0', 'maxscore')
            );
        }
    }

    /**
     * Creates an activity graded out of 100 with its course module, without the repository.
     *
     * @param int $courseid
     * @param int $grademethod one of the Grader::GRADE* constants
     * @return int the instance id
     */
    private function create_instance(int $courseid, int $grademethod): int {
        global $DB;
        $instance               = new stdClass();
        $instance->course       = $courseid;
        $instance->name         = 'H5P ' . $grademethod;
        $instance->introformat  = FORMAT_HTML;
        $instance->object_url   = 'ccrep://repository/' . $grademethod;
        $instance->grade        = 100;
        $instance->grade_method = $grademethod;
        $instance->timecreated  = time();
        $instance->timemodified = time();
        $instance->id           = $DB->insert_record('edusharing', $instance);
        $DB->insert_record('course_modules', [
            'course'   => $courseid,
            'module'   => $DB->get_field('modules', 'id', ['name' => 'edusharing'], MUST_EXIST),
            'instance' => $instance->id,
            'section'  => 0,
            'added'    => time(),
        ]);
        return (int)$instance->id;
    }

    /**
     * Stores a completed attempt as grading\Attempt does, with the given scaled score.
     *
     * @param int $instanceid
     * @param int $userid
     * @param int $attempt
     * @param int $rawscore
     * @param int $maxscore
     * @param float $scaled
     * @return void
     */
    private function create_attempt(int $instanceid, int $userid, int $attempt, int $rawscore, int $maxscore, float $scaled): void {
        global $DB;
        $DB->insert_record('edusharing_attempts', [
            'edusharingid' => $instanceid,
            'userid'       => $userid,
            'timecreated'  => time(),
            'timemodified' => time(),
            'attempt'      => $attempt,
            'rawscore'     => $rawscore,
            'maxscore'     => $maxscore,
            'scaled'       => $scaled,
            'completion'   => 1,
            'success'      => 1,
        ]);
    }
}
