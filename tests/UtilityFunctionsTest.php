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

declare(strict_types = 1);

use core\moodle_database_for_testing;
use mod_edusharing\UtilityFunctions;
use testUtils\FakeConfig;
use testUtils\TestStringGenerator;

/**
 * Class UtilityFunctionsTest
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 */
class UtilityFunctionsTest extends advanced_testcase {
    /**
     * Function test_if_get_object_id_from_url_returns_proper_path_if_url_is_ok
     *
     * @return void
     */
    public function test_if_get_object_id_from_url_returns_proper_path_if_url_is_ok(): void {
        $utils = new UtilityFunctions();
        $this->assertEquals('hallo', $utils->get_object_id_from_url('http://test.com/hallo/'));
    }

    /**
     * Function test_if_get_object_id_from_url_triggers_warning_if_url_is_malformed
     *
     * @return void
     */
    public function test_if_get_object_id_from_url_triggers_warning_if_url_is_malformed(): void {
        $utils = new UtilityFunctions();
        $this->expectWarning();
        $utils->get_object_id_from_url('http://test.com:-80/hallo/');
    }

    /**
     * Function test_if_get_repository_id_from_url_returns_host_if_url_is_ok
     *
     * @return void
     * @throws Exception
     */
    public function test_if_get_repository_id_from_url_returns_host_if_url_is_ok(): void {
        $utils = new UtilityFunctions();
        $this->assertEquals('test.com', $utils->get_repository_id_from_url('http://test.com/hallo/'));
    }

    /**
     * Function test_if_get_repository_throws_exception_if_url_is_malformed
     *
     * @return void
     * @throws Exception
     */
    public function test_if_get_repository_throws_exception_if_url_is_malformed(): void {
        $utils = new UtilityFunctions();
        $this->expectException(Exception::class);
        $utils->get_repository_id_from_url('http://test.com:-80/hallo/');
    }

    /**
     * Function test_if_get_auth_key_returns_user_id_if_sso_is_active
     *
     * @return void
     *
     * @backupGlobals enabled
     * @throws dml_exception
     */
    public function test_if_get_auth_key_returns_user_id_if_sso_is_active(): void {
        global $SESSION, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $fakeconfig = new FakeConfig();
        $fakeconfig->set_entries([
            'EDU_AUTH_PARAM_NAME_USERID' => 'test',
        ]);
        $utils                   = new UtilityFunctions($fakeconfig);
        $SESSION->edusharing_sso = ['test' => 'expectedId'];
        $this->assertEquals('expectedId', $utils->get_auth_key());
    }

    /**
     * Function test_get_auth_key_returns_guest_id_if_guest_option_is_active
     *
     * @return void
     *
     * @backupGlobals enabled
     * @throws dml_exception
     */
    public function test_get_auth_key_returns_guest_id_if_guest_option_is_active(): void {
        global $SESSION, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        unset($SESSION->edusharing_sso);
        $fakeconfig = new FakeConfig();
        $fakeconfig->set_entries([
            'edu_guest_option'   => '1',
            'edu_guest_guest_id' => 'expectedId',
        ]);
        $utils = new UtilityFunctions($fakeconfig);
        $this->assertEquals('expectedId', $utils->get_auth_key());
    }

    /**
     * Function test_get_auth_key_returns_configured_auth_key_if_set
     *
     * @return void
     *
     * @backupGlobals enabled
     * @throws dml_exception
     */
    public function test_get_auth_key_returns_configured_auth_key_if_set(): void {
        global $SESSION, $USER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        unset($SESSION->edusharing_sso);
        $fakeconfig = new FakeConfig();
        $fakeconfig->set_entries([
            'EDU_AUTH_KEY' => 'email',
        ]);
        $utils       = new UtilityFunctions($fakeconfig);
        $USER->email = 'expected@expected.org';
        $this->assertEquals('expected@expected.org', $utils->get_auth_key());
    }

    /**
     * Function test_get_auth_key_returns_auth_key_in_profile_if_all_previous_are_not_met
     *
     * @return void
     *
     * @backupGlobals enabled
     * @throws dml_exception
     */
    public function test_get_auth_key_returns_auth_key_in_profile_if_all_previous_are_not_met(): void {
        global $SESSION, $USER, $CFG;
        unset($SESSION->edusharing_sso);
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $fakeconfig = new FakeConfig();
        $fakeconfig->set_entries([
            'EDU_AUTH_KEY' => 'nonsense',
        ]);
        $utils                     = new UtilityFunctions($fakeconfig);
        $USER->profile['nonsense'] = 'expectedId';
        $this->assertEquals('expectedId', $utils->get_auth_key());
    }

    /**
     * Function test_get_auth_key_returns_user_name_as_last_resort
     *
     * @return void
     *
     * @backupGlobals enabled
     * @throws dml_exception
     */
    public function test_get_auth_key_returns_user_name_as_last_resort(): void {
        global $SESSION, $USER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        unset($SESSION->edusharing_sso);
        $fakeconfig = new FakeConfig();
        $fakeconfig->set_entries([
            'EDU_AUTH_KEY' => 'nonsense',
        ]);
        $utils          = new UtilityFunctions($fakeconfig);
        $USER->username = 'expectedName';
        $this->assertEquals('expectedName', $utils->get_auth_key());
    }

    /**
     * Function test_if_set_module_in_db_finds_matches_and_sets_resource_ids_to_db_if_matches_found
     *
     * @return void
     *
     * @backupGlobals enabled
     */
    public function test_if_set_module_in_db_finds_matches_and_sets_resource_ids_to_db_if_matches_found(): void {
        require_once('lib/dml/tests/dml_test.php');
        $utils  = new UtilityFunctions();
        $idtype = 'testType';
        $data   = ['objectid' => 'value1'];
        $dbmock = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['set_field'])
            ->getMock();
        $dbmock->expects($this->exactly(2))
            ->method('set_field')
            ->withConsecutive(
                ['edusharing', $idtype, 'value1', ['id' => 'resourceID1']],
                ['edusharing', $idtype, 'value1', ['id' => 'resourceID2']],
            );
        // phpcs:ignore -- GLOBALS is supposed to be all caps.
        $GLOBALS['DB'] = $dbmock;
        $text          = '<img resourceId=resourceID1& class="as_edusharing_atto_asda">';
        $text          .= '<a resourceId=resourceID2& class="dsfg_edusharing_atto_afdd">text</a>';
        $utils->set_module_id_in_db($text, $data, $idtype);
    }

    /**
     * Function test_if_set_module_in_db_does_not_set_anything_to_db_if_no_matches_found
     *
     * @return void
     *
     * @backupGlobals enabled
     */
    public function test_if_set_module_in_db_does_not_set_anything_to_db_if_no_matches_found(): void {
        require_once('lib/dml/tests/dml_test.php');
        $utils  = new UtilityFunctions();
        $dbmock = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['set_field'])
            ->getMock();
        $dbmock->expects($this->never())->method('set_field');
        // phpcs:ignore -- GLOBALS is supposed to be all caps.
        $GLOBALS['DB'] = $dbmock;
        $utils->set_module_id_in_db('NothingHere', [], 'idType');
    }

    /**
     * Function test_if_get_course_module_info_returns_proper_info_if_data_found_in_db
     *
     * @return void
     *
     * @backupGlobals enabled
     */
    public function test_if_get_course_module_info_returns_proper_info_if_data_found_in_db(): void {
        require_once('lib/dml/tests/dml_test.php');
        $this->resetAfterTest();
        $utils                   = new UtilityFunctions();
        $module                  = new stdClass();
        $module->instance        = 'instanceId';
        $module->showdescription = false;
        $module->id              = 2;
        $returnone               = new stdClass();
        $returnone->intro        = "myIntro";
        $returnone->introFormat  = '2';
        $returntwo               = new stdClass();
        $returntwo->popup_window = '1';
        $dbmock                  = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['get_record'])
            ->getMock();
        $dbmock->expects($this->exactly(2))
            ->method('get_record')
            ->withConsecutive(
                [],
                ['edusharing', ['id' => 'instanceId'], '*', MUST_EXIST])
            ->willReturnOnConsecutiveCalls($returnone, $returntwo);
        // phpcs:ignore -- GLOBALS is supposed to be all caps.
        $GLOBALS['DB'] = $dbmock;
        $result        = $utils->get_course_module_info($module);
        $this->assertTrue($result instanceof cached_cm_info);
        $this->assertEquals('this.target=\'_blank\';', $result->onclick);
    }

    /**
     * Function test_if_get_course_module_info_returns_false_if_no_record_found
     *
     * @return void
     *
     * @backupGlobals enabled
     */
    public function test_if_get_course_module_info_returns_false_if_no_record_found(): void {
        require_once('lib/dml/tests/dml_test.php');
        $utils = new UtilityFunctions();
        $this->resetAfterTest();
        $module           = new stdClass();
        $module->instance = 'instanceId';
        $module->id       = 2;
        $dbmock           = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['get_record'])
            ->getMock();
        $dbmock->expects($this->once())
            ->method('get_record')
            ->with('edusharing', ['id' => 'instanceId'], 'id, name, intro, introformat', MUST_EXIST)
            ->willThrowException(new Exception());
        // phpcs:ignore -- GLOBALS is supposed to be all caps.
        $GLOBALS['DB'] = $dbmock;
        $this->assertEquals(false, $utils->get_course_module_info($module));
    }

    /**
     * Function test_get_inline_object_matches_returns_only_atto_matches_from_input
     *
     * @return void
     */
    public function test_get_inline_object_matches_returns_only_atto_matches_from_input(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/TestStringGenerator.php');
        $text   = TestStringGenerator::getattoteststring();
        $utils  = new UtilityFunctions();
        $result = $utils->get_inline_object_matches($text);
        $this->assertTrue(count($result) === 4);
        $this->assertTrue(count(array_filter($result, fn($value) => str_contains($value, '</a>'))) === 2);
        $this->assertTrue(count(array_filter($result, fn($value) => str_contains($value, '<img'))) === 2);
    }
}
