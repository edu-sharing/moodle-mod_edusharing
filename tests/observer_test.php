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

// Namespace does not match PSR. But Moodle likes it this way.
namespace mod_edusharing;

use core\event\course_module_deleted;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use testUtils\TestableObserver;

/**
 * Class observer_test
 *
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\mod_edusharing_observer::class)]
final class observer_test extends \advanced_testcase {
    /**
     * Regression test: deleting an activity whose description embeds an
     * edu-sharing object must delete the corresponding usage.
     *
     * The row is found by module_id (stamped there for intro embeds), so
     * course_module_deleted must hand its id to EduSharingService::delete_instance().
     * Previously this used array access ($object['id']) on a stdClass, which
     * raised an uncaught \Error and silently skipped the deletion.
     *
     * @return void
     */
    public function test_course_module_deleted_deletes_usage_for_embedded_object(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/TestableObserver.php');

        // An edu-sharing object embedded in an activity description gets its module_id
        // stamped with the course module id when the activity is saved.
        $cmid                = 42;
        $record              = new stdClass();
        $record->course      = 7;
        $record->module_id   = $cmid;
        $record->name        = 'embedded object';
        $record->introformat = 0;
        $record->object_url  = 'ccrep://repository/1234';
        $record->object_version = '1.0';
        $record->usage_id    = 'usage-abc';
        $record->timecreated = time();
        $record->timemodified = time();
        $rowid = $DB->insert_record('edusharing', $record);

        // Mock the service so no remote repository call is made, and assert that
        // the handler reaches delete_instance with the row id.
        $servicemock = $this->getMockBuilder(EduSharingService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['delete_instance'])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('delete_instance')
            ->with((string)$rowid);
        TestableObserver::$servicemock = $servicemock;

        // The course_module_deleted event reports the deleted course module id as objectid.
        $event = $this->createMock(course_module_deleted::class);
        $event->method('get_data')->willReturn(['objectid' => $cmid]);

        TestableObserver::course_module_deleted($event);
    }
}
