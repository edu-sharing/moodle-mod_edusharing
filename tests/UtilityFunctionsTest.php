<?php declare(strict_types = 1);

use core\moodle_database_for_testing;
use mod_edusharing\UtilityFunctions;
use testUtils\FakeConfig;

/**
 * Class UtilityFunctionsTest
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
class UtilityFunctionsTest extends advanced_testcase
{
    /**
     * Function testIfGetObjectIdFromUrlReturnsProperPathIfUrlIsOk
     *
     * @return void
     */
    public function testIfGetObjectIdFromUrlReturnsProperPathIfUrlIsOk(): void {
        $utils = new UtilityFunctions();
        $this->assertEquals('hallo', $utils->getObjectIdFromUrl('http://test.com/hallo/'));
    }

    /**
     * Function testIfGetObjectIdFromUrlTriggersWarningIfUrlIsMalformed
     *
     * @return void
     */
    public function testIfGetObjectIdFromUrlTriggersWarningIfUrlIsMalformed(): void {
        $utils = new UtilityFunctions();
        $this->expectWarning();
        $utils->getObjectIdFromUrl('http://test.com:-80/hallo/');
    }

    /**
     * Function testIfGetRepositoryIdFromUrlReturnsHostIfUrlIsOk
     *
     * @return void
     * @throws Exception
     */
    public function testIfGetRepositoryIdFromUrlReturnsHostIfUrlIsOk(): void {
        $utils = new UtilityFunctions();
        $this->assertEquals('test.com', $utils->getRepositoryIdFromUrl('http://test.com/hallo/'));
    }

    /**
     * Function testIfGetRepositoryThrowsExceptionIfUrlIsMalformed
     *
     * @return void
     * @throws Exception
     */
    public function testIfGetRepositoryThrowsExceptionIfUrlIsMalformed(): void {
        $utils = new UtilityFunctions();
        $this->expectException(Exception::class);
        $utils->getRepositoryIdFromUrl('http://test.com:-80/hallo/');
    }

    /**
     * Function testIfGetAuthKeyReturnsUserIdIfSsoIsActive
     *
     * @return void
     *
     * @backupGlobals enabled
     * @throws dml_exception
     */
    public function testIfGetAuthKeyReturnsUserIdIfSsoIsActive(): void {
        global $SESSION, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $fakeConfig = new FakeConfig();
        $fakeConfig->setEntries([
            'EDU_AUTH_PARAM_NAME_USERID' => 'test'
        ]);
        $utils                   = new UtilityFunctions($fakeConfig);
        $SESSION->edusharing_sso = ['test' => 'expectedId'];
        $this->assertEquals('expectedId', $utils->getAuthKey());
    }

    /**
     * Function testGetAuthKeyReturnsGuestIdIfGuestOptionIsActive
     *
     * @return void
     *
     * @backupGlobals enabled
     * @throws dml_exception
     */
    public function testGetAuthKeyReturnsGuestIdIfGuestOptionIsActive(): void {
        global $SESSION, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        unset($SESSION->edusharing_sso);
        $fakeConfig = new FakeConfig();
        $fakeConfig->setEntries([
            'edu_guest_option'   => '1',
            'edu_guest_guest_id' => 'expectedId'
        ]);
        $utils = new UtilityFunctions($fakeConfig);
        $this->assertEquals('expectedId', $utils->getAuthKey());
    }

    /**
     * Function testGetAuthKeyReturnsConfiguredAuthKeyIfSet
     *
     * @return void
     *
     * @backupGlobals enabled
     * @throws dml_exception
     */
    public function testGetAuthKeyReturnsConfiguredAuthKeyIfSet(): void {
        global $SESSION, $USER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        unset($SESSION->edusharing_sso);
        $fakeConfig = new FakeConfig();
        $fakeConfig->setEntries([
            'EDU_AUTH_KEY' => 'email'
        ]);
        $utils       = new UtilityFunctions($fakeConfig);
        $USER->email = 'expected@expected.org';
        $this->assertEquals('expected@expected.org', $utils->getAuthKey());
    }

    /**
     * Function testGetAuthKeyReturnsAuthKeyInProfileIsIfAllPreviousAreNotMet
     *
     * @return void
     *
     * @backupGlobals enabled
     * @throws dml_exception
     */
    public function testGetAuthKeyReturnsAuthKeyInProfileIsIfAllPreviousAreNotMet(): void {
        global $SESSION, $USER, $CFG;
        unset($SESSION->edusharing_sso);
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $fakeConfig = new FakeConfig();
        $fakeConfig->setEntries([
            'EDU_AUTH_KEY' => 'nonsense'
        ]);
        $utils                     = new UtilityFunctions($fakeConfig);
        $USER->profile['nonsense'] = 'expectedId';
        $this->assertEquals('expectedId', $utils->getAuthKey());
    }

    /**
     * Function testGetAuthKeyReturnsUserNameAsLastResort
     *
     * @return void
     *
     * @backupGlobals enabled
     * @throws dml_exception
     */
    public function testGetAuthKeyReturnsUserNameAsLastResort(): void {
        global $SESSION, $USER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        unset($SESSION->edusharing_sso);
        $fakeConfig = new FakeConfig();
        $fakeConfig->setEntries([
            'EDU_AUTH_KEY' => 'nonsense'
        ]);
        $utils          = new UtilityFunctions($fakeConfig);
        $USER->username = 'expectedName';
        $this->assertEquals('expectedName', $utils->getAuthKey());
    }

    /**
     * Function testIfSetModuleInDbFindsMatchesAndSetsResourceIdsToDbIfMatchesFound
     *
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testIfSetModuleInDbFindsMatchesAndSetsResourceIdsToDbIfMatchesFound(): void {
        require_once('lib/dml/tests/dml_test.php');
        $utils  = new UtilityFunctions();
        $idType = 'testType';
        $data   = ['objectid' => 'value1'];
        $dbMock = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['set_field'])
            ->getMock();
        $dbMock->expects($this->exactly(2))
            ->method('set_field')
            ->withConsecutive(['edusharing', $idType, 'value1', ['id' => 'resourceID1']], ['edusharing', $idType, 'value1', ['id' => 'resourceID2']]);
        $GLOBALS['DB'] = $dbMock;
        $text          = '<img resourceId=resourceID1& class="as_edusharing_atto_asda"><a resourceId=resourceID2& class="dsfg_edusharing_atto_afdd">text</a>';
        $utils->setModuleIdInDb($text, $data, $idType);
    }

    /**
     * Function testIfSetModuleInDbDoesNotSetAnythingToDbIfNoMatchesFound
     *
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
     * Function testIfGetCourseModuleInfoReturnsProperInfoIfDataFoundInDb
     *
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testIfGetCourseModuleInfoReturnsProperInfoIfDataFoundInDb(): void {
        require_once('lib/dml/tests/dml_test.php');
        $this->resetAfterTest();
        $utils                   = new UtilityFunctions();
        $module                  = new stdClass();
        $module->instance        = 'instanceId';
        $module->showdescription = false;
        $module->id              = 2;
        $returnOne               = new stdClass();
        $returnOne->intro        = "myIntro";
        $returnOne->introFormat  = '2';
        $returnTwo               = new stdClass();
        $returnTwo->popup_window = '1';
        $dbMock                  = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['get_record'])
            ->getMock();
        $dbMock->expects($this->exactly(2))
            ->method('get_record')
            ->withConsecutive(
                [],
                ['edusharing', ['id' => 'instanceId'], '*', MUST_EXIST])
            ->willReturnOnConsecutiveCalls($returnOne, $returnTwo);
        $GLOBALS['DB'] = $dbMock;
        $result        = $utils->getCourseModuleInfo($module);
        $this->assertTrue($result instanceof cached_cm_info);
        $this->assertEquals('this.target=\'_blank\';', $result->onclick);
    }

    /**
     * Function testIfGetCourseModuleInfoReturnsFalseIfNoRecordFound
     *
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testIfGetCourseModuleInfoReturnsFalseIfNoRecordFound(): void {
        require_once('lib/dml/tests/dml_test.php');
        $utils = new UtilityFunctions();
        $this->resetAfterTest();
        $module           = new stdClass();
        $module->instance = 'instanceId';
        $module->id       = 2;
        $dbMock           = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['get_record'])
            ->getMock();
        $dbMock->expects($this->once())
            ->method('get_record')
            ->with('edusharing', ['id' => 'instanceId'], 'id, name, intro, introformat', MUST_EXIST)
            ->willThrowException(new Exception());
        $GLOBALS['DB'] = $dbMock;
        $this->assertEquals(false, $utils->getCourseModuleInfo($module));
    }

    /**
     * Function testGetInlineObjectMatchesReturnsOnlyAttoMatchesFromInputIfAttoIsSetToTrue
     *
     * @return void
     */
    public function testGetInlineObjectMatchesReturnsOnlyAttoMatchesFromInputIfAttoIsSetToTrue(): void {
        $text   = file_get_contents(__DIR__ . '/attoTestString.txt');
        $utils  = new UtilityFunctions();
        $result = $utils->getInlineObjectMatches($text);
        $this->assertTrue(count($result) === 4);
        $this->assertTrue(count(array_filter($result, fn($value) => str_contains($value, '</a>'))) === 2);
        $this->assertTrue(count(array_filter($result, fn($value) => str_contains($value, '<img'))) === 2);
    }
}
