<?php

namespace tests;

use advanced_testcase;
use core\moodle_database_for_testing;
use dml_exception;
use EduSharingApiClient\EduSharingAuthHelper;
use EduSharingApiClient\EduSharingHelperBase;
use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\EduSharingNodeHelperConfig;
use EduSharingApiClient\UrlHandling;
use Exception;
use mod_edusharing\EduSharingService;
use mod_edusharing\UtilityFunctions;
use stdClass;

class EdusharingServiceTest extends advanced_testcase
{
    /**
     * @return void
     *
     * @backupGlobals enabled
     * @throws Exception
     */
    public function testIfGetTicketReturnsExistingTicketIfCachedTicketIsNew(): void {
        $this->resetAfterTest();
        global $USER;
        set_config('application_cc_gui_url', 'www.url.de', 'edusharing');
        set_config('application_private_key', 'pkey123', 'edusharing');
        set_config('application_appid', 'appid123', 'edusharing');
        $service = new EduSharingService();
        $USER->edusharing_userticket = 'testTicket';
        $USER->edusharing_userticketvalidationts = time();
        $this->assertEquals('testTicket', $service->getTicket());
    }

    /**
     * @return void
     *
     * @backupGlobals enabled
     * @throws dml_exception
     * @throws Exception
     */
    public function testIfGetTicketReturnsExistingTicketIfAuthInfoIsOk(): void {
        global $USER;
        unset($USER->edusharing_userticketvalidationts);
        $USER->edusharing_userticket = 'testTicket';
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authMock   = $this->getMockBuilder(EduSharingAuthHelper::class)
            ->setConstructorArgs([$baseHelper])
            ->onlyMethods(['getTicketAuthenticationInfo'])
            ->getMock();
        $authMock->expects($this->once())
            ->method('getTicketAuthenticationInfo')
            ->will($this->returnValue(['statusCode' => 'OK']));
        $nodeConfig  = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHandler = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $service     = new EduSharingService($authMock, $nodeHandler);
        $this->assertEquals('testTicket', $service->getTicket());
        $this->assertTrue(time() - $USER->edusharing_userticketvalidationts < 10);
    }

    /**
     * @backupGlobals enabled
     * @return void
     */
    public function testIfGetTicketReturnsTicketFromAuthHelperIfNoCachedTicketExists(): void {
        global $USER;
        unset($USER->edusharing_userticket);
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authMock   = $this->getMockBuilder(EduSharingAuthHelper::class)
            ->setConstructorArgs([$baseHelper])
            ->onlyMethods(['getTicketForUser', 'getTicketAuthenticationInfo'])
            ->getMock();
        $authMock->expects($this->once())
            ->method('getTicketForUser')
            ->will($this->returnValue('ticketForUser'));
        $utilsMock = $this->getMockBuilder(UtilityFunctions::class)
            ->onlyMethods(['getAuthKey'])
            ->getMock();
        $utilsMock->expects($this->once())
            ->method('getAuthKey')
            ->will($this->returnValue('neverMind'));
        $nodeConfig  = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHandler = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $service     = new EduSharingService($authMock, $nodeHandler, $utilsMock);
        $this->assertEquals('ticketForUser', $service->getTicket());
        $USER->edusharing_userticket = 'testTicket';
    }

    /**
     * @backupGlobals enabled
     * @return void
     */
    public function testIfGetTicketReturnsTicketFromAuthHelperIfTicketIsTooOldAndAuthInfoCallFails(): void {
        global $USER;
        $USER->edusharing_userticket             = 'testTicket';
        $USER->edusharing_userticketvalidationts = 1689769393;
        $baseHelper                              = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authMock                                = $this->getMockBuilder(EduSharingAuthHelper::class)
            ->setConstructorArgs([$baseHelper])
            ->onlyMethods(['getTicketForUser', 'getTicketAuthenticationInfo'])
            ->getMock();
        $authMock->expects($this->once())
            ->method('getTicketForUser')
            ->will($this->returnValue('ticketForUser'));
        $authMock->expects($this->once())
            ->method('getTicketAuthenticationInfo')
            ->will($this->returnValue(['statusCode' => 'NOT_OK']));
        $utilsMock = $this->getMockBuilder(UtilityFunctions::class)
            ->onlyMethods(['getAuthKey'])
            ->getMock();
        $utilsMock->expects($this->once())
            ->method('getAuthKey')
            ->will($this->returnValue('neverMind'));
        $nodeConfig  = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHandler = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $service     = new EduSharingService($authMock, $nodeHandler, $utilsMock);
        $this->assertEquals('ticketForUser', $service->getTicket());
        $USER->edusharing_userticket = 'testTicket';
    }

    /**
     * @backupGlobals enabled
     * @return void
     */
    public function testIfDeleteInstanceCallsDatabaseWithProperParams(): void {
        require_once('lib/dml/tests/dml_test.php');
        $dbRecord              = new stdClass();
        $dbRecord->id          = 'edusharingId123';
        $dbRecord->object_url  = 'test.de';
        $dbRecord->containerId = 'container123';
        $dbRecord->resourceId  = 'resource123';
        $id                    = 1;
        $dbMock                = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['get_record', 'delete_records'])
            ->getMock();
        $dbMock->expects($this->once())
            ->method('get_record')
            ->with('edusharing', ['id'  => $id], '*', MUST_EXIST)
            ->will($this->returnValue($dbRecord));
        $dbMock->expects($this->once())
            ->method('delete_records')
            ->with('edusharing', ['id' => 'edusharingId123']);
        $GLOBALS['DB'] = $dbMock;
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $utilsMock  = $this->getMockBuilder(UtilityFunctions::class)
            ->onlyMethods(['getObjectIdFromUrl'])
            ->getMock();
        $utilsMock->expects($this->once())
            ->method('getObjectIdFromUrl')
            ->with('test.de')
            ->will($this->returnValue('myNodeId123'));
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->setConstructorArgs([$authHelper, $nodeHelper, $utilsMock])
            ->onlyMethods(['getTicket', 'getUsageId', 'deleteUsage'])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('getTicket')
            ->will($this->returnValue('ticket123'));
        $serviceMock->expects($this->once())
            ->method('getUsageId')
            ->will($this->returnValue('usage123'));
        $serviceMock->deleteInstance($id);
    }
}
