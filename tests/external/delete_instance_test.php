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
namespace mod_edusharing\external;

use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

/**
 * Class delete_instance_test
 *
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(DeleteInstance::class)]
final class delete_instance_test extends \advanced_testcase {
    /**
     * Function test_execute_leaves_records_of_other_courses_alone
     *
     * The editor of a page restored before its objects were converted sends the ids of the
     * source course's records along with its own course. They must not be deleted from there.
     *
     * @return void
     */
    public function test_execute_leaves_records_of_other_courses_alone(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course      = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $record                 = new stdClass();
        $record->course         = $othercourse->id;
        $record->name           = 'embedded object';
        $record->introformat    = 0;
        $record->object_url     = 'ccrep://repository/1234';
        $record->object_version = '1.0';
        $record->usage_id       = 'usage-abc';
        $record->timecreated    = time();
        $record->timemodified   = time();
        $id = (int)$DB->insert_record('edusharing', $record);

        $result = DeleteInstance::execute(['id' => $id, 'courseId' => (int)$course->id]);

        // Loading the class may also report its deprecated external_api alias.
        $messages = array_column($this->getDebuggingMessages(), 'message');
        $this->resetDebugging();
        $this->assertNotEmpty(array_filter($messages, fn(string $message): bool => str_contains($message, 'data record')));
        $this->assertSame(['success' => false], $result);
        $this->assertTrue($DB->record_exists('edusharing', ['id' => $id]));
    }
}
