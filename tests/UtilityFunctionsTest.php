<?php

use core\context\module;
use core\moodle_database_for_testing;
use mod_edusharing\UtilityFunctions;

class UtilityFunctionsTest extends advanced_testcase
{
    public function testIfGetObjectIdFromUrlReturnsProperPathIfUrlIsOk(): void {
        $utils = new UtilityFunctions();
        $this->assertEquals('hallo', $utils->getObjectIdFromUrl('http://test.com/hallo/'));
    }

    public function testIfGetObjectIdFromUrlTriggersWarningIfUrlIsMalformed(): void {
        $utils = new UtilityFunctions();
        $this->expectWarning();
        $utils->getObjectIdFromUrl('http://test.com:-80/hallo/');
    }

    public function testIfGetRepositoryIdFromUrlReturnsHostIfUrlIsOk(): void {
        $utils = new UtilityFunctions();
        $this->assertEquals('test.com', $utils->getRepositoryIdFromUrl('http://test.com/hallo/'));
    }

    public function testIfGetRepositoryThrowsExceptionIfUrlIsMalformed(): void {
        $utils = new UtilityFunctions();
        $this->expectException(Exception::class);
        $utils->getRepositoryIdFromUrl('http://test.com:-80/hallo/');
    }

    /**
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testIfGetAuthKeyReturnsUserIdIfSsoIsActive(): void {
        global $SESSION;
        $utils = new UtilityFunctions();
        $this->resetAfterTest();
        set_config('EDU_AUTH_PARAM_NAME_USERID', 'test', 'edusharing');
        $SESSION->edusharing_sso = ['test' => 'expectedId'];
        $this->assertEquals('expectedId', $utils->getAuthKey());
    }

    /**
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testGetAuthKeyReturnsGuestIdIfGuestOptionIsActive(): void {
        global $SESSION;
        $utils = new UtilityFunctions();
        $this->resetAfterTest();
        unset($SESSION->edusharing_sso);
        set_config('edu_guest_option', '1', 'edusharing');
        set_config('edu_guest_guest_id', 'expectedId', 'edusharing');
        $this->assertEquals('expectedId', $utils->getAuthKey());
    }

    /**
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testGetAuthKeyReturnsConfiguredAuthKeyIfSet(): void {
        global $SESSION, $USER;
        $utils = new UtilityFunctions();
        $this->resetAfterTest();
        unset($SESSION->edusharing_sso);
        unset_config('edu_guest_option', 'edusharing');
        unset_config('edu_guest_guest_id', 'edusharing');
        set_config('EDU_AUTH_KEY', 'email', 'edusharing');
        $USER->email = 'expected@expected.org';
        $this->assertEquals('expected@expected.org', $utils->getAuthKey());
    }

    /**
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testGetAuthKeyReturnsAuthKeyInProfileIsIfAllPreviousAreNotMet(): void {
        global $SESSION, $USER;
        $utils = new UtilityFunctions();
        $this->resetAfterTest();
        unset($SESSION->edusharing_sso);
        unset_config('edu_guest_option', 'edusharing');
        unset_config('edu_guest_guest_id', 'edusharing');
        set_config('EDU_AUTH_KEY', 'nonsense', 'edusharing');
        $USER->profile['nonsense'] = 'expectedId';
        $this->assertEquals('expectedId', $utils->getAuthKey());
    }

    /**
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testGetAuthKeyReturnsUserNameAsLastResort(): void {
        global $SESSION, $USER;
        $utils = new UtilityFunctions();
        $this->resetAfterTest();
        unset($SESSION->edusharing_sso);
        unset_config('edu_guest_option', 'edusharing');
        unset_config('edu_guest_guest_id', 'edusharing');
        set_config('EDU_AUTH_KEY', 'nonsense', 'edusharing');
        $USER->username = 'expectedName';
        $this->assertEquals('expectedName', $utils->getAuthKey());
    }

    /**
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testIfSetModuleInDbFindsMatchesAndSetsResourceIdsToDbIfMatchesFound(): void {
        require_once('lib/dml/tests/dml_test.php');
        $utils = new UtilityFunctions();
        $idType = 'testType';
        $data   = ['objectid' => 'value1'];
        $dbMock = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['set_field'])
            ->getMock();
        $dbMock->expects($this->exactly(2))
            ->method('set_field')
            ->withConsecutive(['edusharing', $idType, 'value1', ['id' => 'resourceID1']], ['edusharing', $idType, 'value1', ['id' => 'resourceID2']]);
        $GLOBALS['DB'] = $dbMock;
        $text = '<img resourceId=resourceID1& class="as_edusharing_atto_asda"><a resourceId=resourceID2& class="dsfg_edusharing_atto_afdd">text</a>';
        $utils->setModuleIdInDb($text, $data, $idType);
    }

    /**
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testIfSetModuleInDbDoesNotSetAnythingToDbIfNoMatchesFound(): void {
        require_once('lib/dml/tests/dml_test.php');
        $utils  = new UtilityFunctions();
        $dbMock = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['set_field'])
            ->getMock();
        $dbMock->expects($this->never())->method('set_field');
        $GLOBALS['DB'] = $dbMock;
        $utils->setModuleIdInDb('NothingHere', [], 'idType');
    }

    /**
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testIfGetCourseModuleInfoReturnsProperInfoIfDataFoundInDb(): void {
        require_once('lib/dml/tests/dml_test.php');
        $this->resetAfterTest();
        $utils  = new UtilityFunctions();
        $module = new stdClass();
        $module->instance        = 'instanceId';
        $module->showdescription = false;
        $module->id              = 2;
        $returnOne               = new stdClass();
        $returnOne->intro        = "myIntro";
        $returnOne->introFormat  = '2';
        $returnTwo               = new stdClass();
        $returnTwo->popup_window = '1';
        $dbMock = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['get_record'])
            ->getMock();
        $dbMock->expects($this->exactly(2))
            ->method('get_record')
            ->withConsecutive(
                [],
                ['edusharing', ['id' => 'instanceId'], '*', MUST_EXIST])
            ->willReturnOnConsecutiveCalls($returnOne, $returnTwo);
        $GLOBALS['DB'] = $dbMock;
        $result = $utils->getCourseModuleInfo($module);
        $this->assertTrue($result instanceof cached_cm_info);
        $this->assertEquals('this.target=\'_blank\';', $result->onclick);
    }

    /**
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testIfGetCourseModuleInfoReturnsFalseIfNoRecordFound(): void {
        require_once('lib/dml/tests/dml_test.php');
        $utils  = new UtilityFunctions();
        $this->resetAfterTest();
        $module                  = new stdClass();
        $module->instance         = 'instanceId';
        $module->id = 2;
        $dbMock = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['get_record'])
            ->getMock();
        $dbMock->expects($this->once())
            ->method('get_record')
            ->with('edusharing', ['id' => 'instanceId'], 'id, name, intro, introformat', MUST_EXIST)
            ->willThrowException(new Exception());
        $GLOBALS['DB'] = $dbMock;
        $this->assertEquals(false, $utils->getCourseModuleInfo($module));
    }
}
