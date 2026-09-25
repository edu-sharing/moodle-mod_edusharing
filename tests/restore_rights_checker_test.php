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

use EduSharingApiClient\CurlResult;
use EduSharingApiClient\EduSharingHelperBase;
use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\EduSharingNodeHelperConfig;
use EduSharingApiClient\NodeDeletedException;
use EduSharingApiClient\UrlHandling;
use backup;
use backup_controller;
use core_backup\hook\after_restore_root_define_settings;
use Exception;
use mod_edusharing\local\hook_callbacks;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

/**
 * Class restore_rights_checker_test
 *
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(RestoreRightsChecker::class)]
#[CoversClass(RestoreHelper::class)]
#[CoversClass(EduSharingNodeHelper::class)]
#[CoversClass(hook_callbacks::class)]
#[CoversClass(\restore_edusharing_activity_task::class)]
final class restore_rights_checker_test extends \advanced_testcase {
    /**
     * Function setUp
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        RestoreRightsChecker::reset_cache();
    }

    /**
     * Function get_service_mock
     *
     * @return EduSharingService&MockObject
     */
    private function get_service_mock(): EduSharingService {
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
        $service = $this->getMockBuilder(EduSharingService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_node_for_user', 'get_ticket', 'get_ticket_for_user', 'create_usage'])
            ->getMock();
        $service->method('get_ticket')->willReturn('ticket');
        $service->method('get_ticket_for_user')->willReturn('ticket');
        return $service;
    }

    /**
     * Function get_embed
     *
     * @param string $nodeid
     * @param string $title
     * @return string
     */
    private function get_embed(string $nodeid, string $title): string {
        $query = http_build_query([
            'resourceId'     => 1,
            'object_url'     => 'ccrep://repo/' . $nodeid,
            'title'          => $title,
            'window_version' => '1.0',
            'nodeId'         => $nodeid,
        ], '', '&amp;');
        return '<a class="edusharing_atto" href="https://other.site/mod/edusharing/preview.php?' . $query . '">' . $title . '</a>';
    }

    /**
     * Function test_can_publish_requires_ccpublish_in_access
     *
     * @return void
     */
    public function test_can_publish_requires_ccpublish_in_access(): void {
        $this->resetAfterTest();
        $user    = $this->getDataGenerator()->create_user();
        $service = $this->get_service_mock();
        $service->expects($this->exactly(2))
            ->method('get_node_for_user')
            ->willReturnCallback(fn(string $ticket, string $nodeid): array => [
                'node' => ['access' => $nodeid === 'allowed' ? ['Read', 'CCPublish'] : ['Read']],
            ]);
        $checker = new RestoreRightsChecker($service);

        $this->assertTrue($checker->can_publish('allowed', (int)$user->id));
        $this->assertFalse($checker->can_publish('denied', (int)$user->id));
        // Answered from the cache, the service is not asked again.
        $this->assertTrue($checker->can_publish('allowed', (int)$user->id));
        $this->assertTrue((new RestoreRightsChecker($service))->can_publish('allowed', (int)$user->id));
    }

    /**
     * Function test_can_publish_is_false_on_error
     *
     * @return void
     */
    public function test_can_publish_is_false_on_error(): void {
        $this->resetAfterTest();
        $service = $this->get_service_mock();
        $service->method('get_node_for_user')->willThrowException(new Exception('repository down'));
        $checker = new RestoreRightsChecker($service);

        $this->assertFalse($checker->can_publish('node', null));
        $this->assertDebuggingCalled();
        $this->assertFalse($checker->can_publish('', null));
    }

    /**
     * Function test_collect_backup_objects_finds_activities_and_embedded_objects
     *
     * @return void
     */
    public function test_collect_backup_objects_finds_activities_and_embedded_objects(): void {
        $basepath = make_request_directory();
        mkdir($basepath . '/sections/section_1', 0777, true);
        mkdir($basepath . '/activities/edusharing_2', 0777, true);
        mkdir($basepath . '/activities/label_3', 0777, true);
        file_put_contents(
            $basepath . '/sections/section_1/section.xml',
            '<?xml version="1.0" encoding="UTF-8"?><section id="1"><summary>'
            . s('<p>' . $this->get_embed('node-summary', 'In summary') . '</p>') . '</summary></section>'
        );
        file_put_contents(
            $basepath . '/activities/edusharing_2/edusharing.xml',
            '<?xml version="1.0" encoding="UTF-8"?><activity id="2" moduleid="2" modulename="edusharing">'
            . '<edusharing id="2"><name>My activity</name><intro></intro>'
            . '<object_url>ccrep://repo/node-activity</object_url></edusharing></activity>'
        );
        file_put_contents(
            $basepath . '/activities/label_3/label.xml',
            '<?xml version="1.0" encoding="UTF-8"?><activity id="3" moduleid="3" modulename="label">'
            . '<label id="3"><intro>' . s($this->get_embed('node-label', 'In label')) . '</intro></label></activity>'
        );
        $info = new stdClass();
        $info->sections   = ['section_1' => (object)['sectionid' => 1, 'directory' => 'sections/section_1']];
        $info->activities = [
            'edusharing_2' => (object)['moduleid' => 2, 'modulename' => 'edusharing', 'title' => 'My activity',
                'directory' => 'activities/edusharing_2'],
            'label_3'      => (object)['moduleid' => 3, 'modulename' => 'label', 'title' => 'Label',
                'directory' => 'activities/label_3'],
        ];

        $objects = (new RestoreRightsChecker($this->get_service_mock()))->collect_backup_objects($basepath, $info);

        $this->assertSame([
            ['nodeid' => 'node-summary', 'title' => 'In summary', 'kind' => 'text', 'moduleid' => null],
            ['nodeid' => 'node-activity', 'title' => 'My activity', 'kind' => 'activity', 'moduleid' => 2],
            ['nodeid' => 'node-label', 'title' => 'In label', 'kind' => 'text', 'moduleid' => null],
        ], $objects);
    }

    /**
     * Function test_convert_inline_options_strips_objects_without_publish_rights
     *
     * @return void
     */
    public function test_convert_inline_options_strips_objects_without_publish_rights(): void {
        global $DB;
        $this->resetAfterTest();
        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 0], '*', MUST_EXIST);
        $section->summary = '<p>before</p>' . $this->get_embed('denied', 'Denied object') . '<p>after</p>';
        $DB->update_record('course_sections', $section);

        $service = $this->get_service_mock();
        $service->method('get_node_for_user')->willReturn(['node' => ['access' => ['Read']]]);
        $service->expects($this->never())->method('create_usage');

        (new RestoreHelper($service))->convert_inline_options((int)$course->id, (int)$user->id);

        $summary = $DB->get_field('course_sections', 'summary', ['id' => $section->id]);
        $this->assertStringNotContainsString('edusharing_atto', $summary);
        $this->assertStringContainsString('before', $summary);
        $this->assertStringContainsString('after', $summary);
        $this->assertSame(0, $DB->count_records('edusharing', ['course' => $course->id]));
    }

    /**
     * Function get_node_helper
     *
     * @param CurlResult $result
     * @param array $requests receives [url, curl options] of each request made
     * @return EduSharingNodeHelper
     */
    private function get_node_helper(CurlResult $result, array &$requests): EduSharingNodeHelper {
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
        $base = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs(['https://repo.test/edu-sharing', 'pkey', 'appid'])
            ->onlyMethods(['handleCurlRequest', 'sign', 'getAlgorithm'])
            ->getMock();
        $base->method('sign')->willReturn('signature');
        $base->method('getAlgorithm')->willReturn('RSA-SHA256');
        $base->method('handleCurlRequest')->willReturnCallback(
            function (string $url, array $options) use ($result, &$requests): CurlResult {
                $requests[] = [$url, $options];
                return $result;
            }
        );
        return new EduSharingNodeHelper($base, new EduSharingNodeHelperConfig(new UrlHandling(false)));
    }

    /**
     * Function test_get_node_by_ticket_authenticates_as_user_without_usage
     *
     * @return void
     */
    public function test_get_node_by_ticket_authenticates_as_user_without_usage(): void {
        $requests = [];
        $body     = json_encode(['node' => ['ref' => ['id' => 'node-1'], 'access' => ['Read', 'CCPublish']]]);
        $helper   = $this->get_node_helper(new CurlResult($body, 0, ['http_code' => 200]), $requests);

        $data = $helper->getNodeByTicket('ticket-1', 'node-1');

        $this->assertSame(['Read', 'CCPublish'], $data['node']['access']);
        $this->assertCount(1, $requests);
        [$url, $options] = $requests[0];
        $this->assertSame('https://repo.test/edu-sharing/rest/node/v1/nodes/-home-/node-1/metadata', $url);
        $headers = $options[CURLOPT_HTTPHEADER];
        $this->assertContains('Authorization: EDU-TICKET ticket-1', $headers);
        $this->assertContains('X-Edu-App-Id: appid', $headers);
        $this->assertEmpty(array_filter($headers, fn(string $header): bool => str_starts_with($header, 'X-Edu-Usage-')));
    }

    /**
     * Function test_get_node_by_ticket_throws_on_missing_node_and_errors
     *
     * @return void
     */
    public function test_get_node_by_ticket_throws_on_missing_node_and_errors(): void {
        $requests = [];
        $helper   = $this->get_node_helper(new CurlResult('', 0, ['http_code' => 404]), $requests);
        try {
            $helper->getNodeByTicket('ticket-1', 'gone');
            $this->fail('A missing node must throw');
        } catch (NodeDeletedException $exception) {
            $this->assertStringContainsString('gone', $exception->getMessage());
        }

        $body   = json_encode(['error' => 'org.alfresco.AccessDenied', 'message' => 'no read']);
        $helper = $this->get_node_helper(new CurlResult($body, 0, ['http_code' => 403]), $requests);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('fetching node by ticket failed 403');
        $helper->getNodeByTicket('ticket-1', 'secret');
    }

    /**
     * Function test_hook_warns_about_objects_without_publish_rights
     *
     * @return void
     */
    public function test_hook_warns_about_objects_without_publish_rights(): void {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        $this->resetAfterTest();
        $user     = $this->getDataGenerator()->create_user();
        $basepath = make_request_directory();
        mkdir($basepath . '/sections/section_1', 0777, true);
        file_put_contents(
            $basepath . '/sections/section_1/section.xml',
            '<?xml version="1.0" encoding="UTF-8"?><section id="1"><summary>'
            . s($this->get_embed('allowed', 'Allowed object') . $this->get_embed('denied', 'Denied object'))
            . '</summary></section>'
        );
        $info = (object)[
            'sections'   => ['section_1' => (object)['sectionid' => 1, 'directory' => 'sections/section_1']],
            'activities' => [],
        ];
        // Prime the checker, as the hook creates its own one.
        $service = $this->get_service_mock();
        $service->method('get_node_for_user')->willReturnCallback(fn(string $ticket, string $nodeid): array => [
            'node' => ['access' => $nodeid === 'allowed' ? ['CCPublish'] : []],
        ]);
        $checker = new RestoreRightsChecker($service);
        $checker->can_publish('allowed', (int)$user->id);
        $checker->can_publish('denied', (int)$user->id);

        $task = $this->getMockBuilder(\restore_root_task::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_basepath', 'get_info', 'get_userid', 'log'])
            ->getMock();
        $task->method('get_basepath')->willReturn($basepath);
        $task->method('get_info')->willReturn($info);
        $task->method('get_userid')->willReturn($user->id);
        $task->expects($this->once())
            ->method('log')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('Denied object'),
                    $this->logicalNot($this->stringContains('Allowed object'))
                ),
                backup::LOG_WARNING
            );

        hook_callbacks::after_restore_root_define_settings(new after_restore_root_define_settings($task));
    }

    /**
     * Function test_restore_excludes_activities_without_publish_rights
     *
     * @return void
     */
    public function test_restore_excludes_activities_without_publish_rights(): void {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/course/lib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $course   = $this->getDataGenerator()->create_course();
        $moduleid = (int)$DB->get_field('modules', 'id', ['name' => 'edusharing'], MUST_EXIST);
        // Created by hand, adding an instance the regular way would ask the repository for a usage.
        $instanceid = $DB->insert_record('edusharing', (object)[
            'course'         => $course->id,
            'name'           => 'Denied activity',
            'intro'          => '',
            'introformat'    => FORMAT_HTML,
            'object_url'     => 'ccrep://repo/denied-activity',
            'object_version' => '1.0',
            'timecreated'    => time(),
            'timemodified'   => time(),
        ]);
        $cmid = add_course_module((object)[
            'course' => $course->id, 'module' => $moduleid, 'instance' => $instanceid, 'section' => 0, 'visible' => 1,
        ]);
        course_add_cm_to_section($course->id, $cmid, 0);

        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id
        );
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        // Prime the checker, as the restore creates its own ones.
        $service = $this->get_service_mock();
        $service->method('get_node_for_user')->willReturn(['node' => ['access' => ['Read']]]);
        $this->assertFalse((new RestoreRightsChecker($service))->can_publish('denied-activity', (int)$USER->id));

        $newcourse = $this->getDataGenerator()->create_course();
        $rc = new \restore_controller(
            $backupid,
            $newcourse->id,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id,
            backup::TARGET_EXISTING_ADDING
        );
        $included = $rc->get_plan()->get_setting('edusharing_' . $cmid . '_included');
        $this->assertFalse((bool)$included->get_value());
        $this->assertSame(\base_setting::LOCKED_BY_CONFIG, $included->get_status());
        $this->assertStringContainsString('Denied activity', $included->get_ui()->get_label());

        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        $this->assertSame(0, $DB->count_records('course_modules', ['course' => $newcourse->id, 'module' => $moduleid]));
        $this->assertSame(0, $DB->count_records('edusharing', ['course' => $newcourse->id]));
        // The course_restored observer talks to the unconfigured repository.
        $this->resetDebugging();
    }
}
