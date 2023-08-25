<?php declare(strict_types=1);

use core\moodle_database_for_testing;
use EduSharingApiClient\CurlHandler as EdusharingCurlHandler;
use EduSharingApiClient\CurlResult;
use EduSharingApiClient\EduSharingAuthHelper;
use EduSharingApiClient\EduSharingHelperBase;
use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\EduSharingNodeHelperConfig;
use EduSharingApiClient\NodeDeletedException;
use EduSharingApiClient\UrlHandling;
use EduSharingApiClient\Usage;
use EduSharingApiClient\UsageDeletedException;
use mod_edusharing\EduSharingService;
use mod_edusharing\MoodleCurlHandler;
use mod_edusharing\UtilityFunctions;
use testUtils\FakeConfig;

/**
 * Class EdusharingServiceTest
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
class EdusharingServiceTest extends advanced_testcase
{
    /**
     * Function testIfGetTicketReturnsExistingTicketIfCachedTicketIsNew
     *
     * @return void
     *
     * @backupGlobals enabled
     * @throws Exception
     */
    public function testIfGetTicketReturnsExistingTicketIfCachedTicketIsNew(): void {
        global $USER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $fakeConfig = new FakeConfig();
        $fakeConfig->setEntries([
            'application_cc_gui_url'  => 'www.url.de',
            'application_private_key' => 'pkey123',
            'application_appid'       => 'appid123'
        ]);
        $utils                                   = new UtilityFunctions($fakeConfig);
        $service                                 = new EduSharingService(utils: $utils);
        $USER->edusharing_userticket             = 'testTicket';
        $USER->edusharing_userticketvalidationts = time();
        $this->assertEquals('testTicket', $service->getTicket());
    }

    /**
     * Function testIfGetTicketReturnsExistingTicketIfAuthInfoIsOk
     *
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
        $baseHelper                  = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authMock                    = $this->getMockBuilder(EduSharingAuthHelper::class)
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
     * Function testIfGetTicketReturnsTicketFromAuthHelperIfNoCachedTicketExists
     *
     * @backupGlobals enabled
     * @return void
     * @throws dml_exception
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
     * Function testIfGetTicketReturnsTicketFromAuthHelperIfTicketIsTooOldAndAuthInfoCallFails
     *
     * @backupGlobals enabled
     * @return void
     * @throws dml_exception
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
     * Function testIfCreateUsageCallsNodeHelperMethodWithCorrectParams
     */
    public function testIfCreateUsageCallsNodeHelperMethodWithCorrectParams(): void {
        $usageObject              = new stdClass();
        $usageObject->containerId = 'containerIdTest';
        $usageObject->resourceId  = 'resourceIdTest';
        $usageObject->nodeId      = 'nodeIdTest';
        $usageObject->nodeVersion = 'nodeVersion';
        $baseHelper               = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $nodeConfig               = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper               = new EduSharingAuthHelper($baseHelper);
        $nodeHelperMock           = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->onlyMethods(['createUsage'])
            ->setConstructorArgs([$baseHelper, $nodeConfig])
            ->getMock();
        $nodeHelperMock->expects($this->once())
            ->method('createUsage')
            ->with('ticketTest', 'containerIdTest', 'resourceIdTest', 'nodeIdTest', 'nodeVersion');
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['getTicket'])
            ->setConstructorArgs([$authHelper, $nodeHelperMock])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('getTicket')
            ->will($this->returnValue('ticketTest'));
        $serviceMock->createUsage($usageObject);
    }

    /**
     * Function testIfGetUsageIdCallsNodeHelperMethodWithCorrectParamsAndReturnsResult
     *
     * @return void
     * @throws dml_exception
     */
    public function testIfGetUsageIdCallsNodeHelperMethodWithCorrectParamsAndReturnsResult(): void {
        $usageObject              = new stdClass();
        $usageObject->containerId = 'containerIdTest';
        $usageObject->resourceId  = 'resourceIdTest';
        $usageObject->nodeId      = 'nodeIdTest';
        $usageObject->ticket      = 'ticketTest';
        $baseHelper               = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $nodeConfig               = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper               = new EduSharingAuthHelper($baseHelper);
        $nodeHelperMock           = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->onlyMethods(['getUsageIdByParameters'])
            ->setConstructorArgs([$baseHelper, $nodeConfig])
            ->getMock();
        $nodeHelperMock->expects($this->once())
            ->method('getUsageIdByParameters')
            ->with('ticketTest', 'nodeIdTest', 'containerIdTest', 'resourceIdTest')
            ->will($this->returnValue('expectedId'));
        $service = new EduSharingService($authHelper, $nodeHelperMock);
        $id      = $service->getUsageId($usageObject);
        $this->assertEquals('expectedId', $id);
    }

    /**
     * Function testIfGetUsageIdThrowsExceptionIfNodeHelperMethodReturnsNull
     *
     * @return void
     * @throws dml_exception
     */
    public function testIfGetUsageIdThrowsExceptionIfNodeHelperMethodReturnsNull(): void {
        $usageObject              = new stdClass();
        $usageObject->containerId = 'containerIdTest';
        $usageObject->resourceId  = 'resourceIdTest';
        $usageObject->nodeId      = 'nodeIdTest';
        $usageObject->ticket      = 'ticketTest';
        $baseHelper               = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $nodeConfig               = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper               = new EduSharingAuthHelper($baseHelper);
        $nodeHelperMock           = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->onlyMethods(['getUsageIdByParameters'])
            ->setConstructorArgs([$baseHelper, $nodeConfig])
            ->getMock();
        $nodeHelperMock->expects($this->once())
            ->method('getUsageIdByParameters')
            ->with('ticketTest', 'nodeIdTest', 'containerIdTest', 'resourceIdTest')
            ->will($this->returnValue(null));
        $service = new EduSharingService($authHelper, $nodeHelperMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No usage found');
        $service->getUsageId($usageObject);
    }

    /**
     * Function testIfDeleteUsageCallsNodeHelperMethodWithProperParams
     *
     * @return void
     * @throws dml_exception
     */
    public function testIfDeleteUsageCallsNodeHelperMethodWithProperParams(): void {
        $usageObject          = new stdClass();
        $usageObject->nodeId  = 'nodeIdTest';
        $usageObject->usageId = 'usageIdTest';
        $baseHelper           = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $nodeConfig           = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper           = new EduSharingAuthHelper($baseHelper);
        $nodeHelperMock       = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->onlyMethods(['deleteUsage'])
            ->setConstructorArgs([$baseHelper, $nodeConfig])
            ->getMock();
        $nodeHelperMock->expects($this->once())
            ->method('deleteUsage')
            ->with('nodeIdTest', 'usageIdTest');
        $service = new EduSharingService($authHelper, $nodeHelperMock);
        $service->deleteUsage($usageObject);
    }

    /**
     * Function testIfGetNodeCallsNodeHelperMethodWithProperParams
     *
     * @return void
     * @throws JsonException
     * @throws NodeDeletedException
     * @throws UsageDeletedException
     * @throws dml_exception
     */
    public function testIfGetNodeCallsNodeHelperMethodWithProperParams(): void {
        $usageObject              = new stdClass();
        $usageObject->nodeId      = 'nodeIdTest';
        $usageObject->usageId     = 'usageIdTest';
        $usageObject->nodeVersion = 'nodeVersionTest';
        $usageObject->containerId = 'containerIdTest';
        $usageObject->resourceId  = 'resourceIdTest';
        $usage                    = new Usage($usageObject->nodeId, $usageObject->nodeVersion, $usageObject->containerId, $usageObject->resourceId, $usageObject->usageId);
        $baseHelper               = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $nodeConfig               = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper               = new EduSharingAuthHelper($baseHelper);
        $nodeHelperMock           = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->onlyMethods(['getNodeByUsage'])
            ->setConstructorArgs([$baseHelper, $nodeConfig])
            ->getMock();
        $nodeHelperMock->expects($this->once())
            ->method('getNodeByUsage')
            ->with($usage);
        $service = new EduSharingService($authHelper, $nodeHelperMock);
        $service->getNode($usageObject);
    }

    /**
     * testIfUpdateInstanceCallsDbMethodsAndCallsCreationMethodWithProperParams
     *
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testIfUpdateInstanceCallsDbMethodsAndCallsCreationMethodWithProperParams(): void {
        require_once('lib/dml/tests/dml_test.php');
        $currentTime                   = time();
        $eduObject                     = new stdClass();
        $eduObject->object_url         = 'inputUrl';
        $eduObject->course             = 'containerIdTest';
        $eduObject->object_version     = 'nodeVersionTest';
        $eduObject->id                 = 'resourceIdTest';
        $eduObjectUpdate               = clone($eduObject);
        $eduObjectUpdate->usage_id     = '2';
        $eduObjectUpdate->timecreated  = $currentTime;
        $eduObjectUpdate->timeupdated  = $currentTime;
        $eduObjectUpdate->options      = '';
        $eduObjectUpdate->popup_window = '';
        $eduObjectUpdate->tracking     = 0;
        $usageData                     = new stdClass();
        $usageData->containerId        = 'containerIdTest';
        $usageData->resourceId         = 'resourceIdTest';
        $usageData->nodeId             = 'outputUrl';
        $usageData->nodeVersion        = 'nodeVersionTest';
        $usageData->ticket             = 'ticketTest';
        $memento                       = new stdClass();
        $memento->id                   = 'someId';
        $baseHelper                    = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $nodeConfig                    = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper                    = new EduSharingAuthHelper($baseHelper);
        $nodeHelper                    = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $utilsMock                     = $this->getMockBuilder(UtilityFunctions::class)
            ->onlyMethods(['getObjectIdFromUrl'])
            ->getMock();
        $utilsMock->expects($this->once())
            ->method('getObjectIdFromUrl')
            ->with('inputUrl')
            ->will($this->returnValue('outputUrl'));
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['createUsage', 'getTicket'])
            ->setConstructorArgs([$authHelper, $nodeHelper, $utilsMock])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('getTicket')
            ->will($this->returnValue('ticketTest'));
        $serviceMock->expects($this->once())
            ->method('createUsage')
            ->with($usageData)
            ->will($this->returnValue(new Usage('whatever', 'whatever', 'whatever', 'whatever', '2')));
        $dbMock = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['get_record', 'update_record'])
            ->getMock();
        $dbMock->expects($this->once())
            ->method('get_record')
            ->with('edusharing', ['id' => 'resourceIdTest'], '*', MUST_EXIST)
            ->will($this->returnValue($memento));
        $dbMock->expects($this->once())
            ->method('update_record')
            ->with('edusharing', $eduObjectUpdate);
        $GLOBALS['DB'] = $dbMock;
        $this->assertEquals(true, $serviceMock->updateInstance($eduObject, $currentTime));
    }

    /**
     * Function testIfUpdateInstanceResetsDataAndReturnsFalseOnUpdateError
     *
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testIfUpdateInstanceResetsDataAndReturnsFalseOnUpdateError(): void {
        require_once('lib/dml/tests/dml_test.php');
        $currentTime                   = time();
        $eduObject                     = new stdClass();
        $eduObject->object_url         = 'inputUrl';
        $eduObject->course             = 'containerIdTest';
        $eduObject->object_version     = 'nodeVersionTest';
        $eduObject->id                 = 'resourceIdTest';
        $eduObjectUpdate               = clone($eduObject);
        $eduObjectUpdate->usage_id     = '2';
        $eduObjectUpdate->timecreated  = $currentTime;
        $eduObjectUpdate->timeupdated  = $currentTime;
        $eduObjectUpdate->options      = '';
        $eduObjectUpdate->popup_window = '';
        $eduObjectUpdate->tracking     = 0;
        $usageData                     = new stdClass();
        $usageData->containerId        = 'containerIdTest';
        $usageData->resourceId         = 'resourceIdTest';
        $usageData->nodeId             = 'outputUrl';
        $usageData->nodeVersion        = 'nodeVersionTest';
        $usageData->ticket             = 'ticketTest';
        $memento                       = new stdClass();
        $memento->id                   = 'someId';
        $baseHelper                    = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $nodeConfig                    = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper                    = new EduSharingAuthHelper($baseHelper);
        $nodeHelper                    = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $utilsMock                     = $this->getMockBuilder(UtilityFunctions::class)
            ->onlyMethods(['getObjectIdFromUrl'])
            ->getMock();
        $utilsMock->expects($this->once())
            ->method('getObjectIdFromUrl')
            ->with('inputUrl')
            ->will($this->returnValue('outputUrl'));
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['createUsage', 'getTicket'])
            ->setConstructorArgs([$authHelper, $nodeHelper, $utilsMock])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('getTicket')
            ->will($this->returnValue('ticketTest'));
        $serviceMock->expects($this->once())
            ->method('createUsage')
            ->with($usageData)
            ->willThrowException(new Exception(''));
        $dbMock = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['get_record', 'update_record'])
            ->getMock();
        $dbMock->expects($this->once())
            ->method('get_record')
            ->with('edusharing', ['id' => 'resourceIdTest'], '*', MUST_EXIST)
            ->will($this->returnValue($memento));
        $dbMock->expects($this->once())
            ->method('update_record')
            ->with('edusharing', $memento);
        $GLOBALS['DB'] = $dbMock;
        $this->assertEquals(false, $serviceMock->updateInstance($eduObject, $currentTime));
    }

    /**
     * Function testIfAddInstanceCallsDbFunctionsAndServiceMethodWithCorrectParameters
     *
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testIfAddInstanceCallsDbFunctionsAndServiceMethodWithCorrectParameters(): void {
        require_once('lib/dml/tests/dml_test.php');
        $currentTime                        = time();
        $eduObject                          = new stdClass();
        $eduObject->object_url              = 'inputUrl';
        $eduObject->course                  = 'containerIdTest';
        $eduObject->object_version          = '1';
        $eduObject->id                      = 'resourceIdTest';
        $processedEduObject                 = clone($eduObject);
        $processedEduObject->object_version = '';
        $processedEduObject->timecreated    = $currentTime;
        $processedEduObject->timemodified   = $currentTime;
        $processedEduObject->timeupdated    = $currentTime;
        $processedEduObject->options        = '';
        $processedEduObject->popup_window   = '';
        $processedEduObject->tracking       = 0;
        $insertedEduObject                  = clone($processedEduObject);
        $insertedEduObject->id              = 3;
        $insertedEduObject->usage_id        = 4;
        $insertedEduObject->object_version  = 'nodeVersionTest';
        $usageData                          = new stdClass();
        $usageData->containerId             = 'containerIdTest';
        $usageData->resourceId              = 3;
        $usageData->nodeId                  = 'outputUrl';
        $usageData->nodeVersion             = '';
        $dbMock                             = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['insert_record', 'update_record', 'delete_records'])
            ->getMock();
        $dbMock->expects($this->once())
            ->method('insert_record')
            ->with('edusharing', $processedEduObject)
            ->will($this->returnValue(3));
        $dbMock->expects($this->once())
            ->method('update_record')
            ->with('edusharing', $insertedEduObject);
        $GLOBALS['DB'] = $dbMock;
        $utilsMock     = $this->getMockBuilder(UtilityFunctions::class)
            ->onlyMethods(['getObjectIdFromUrl'])
            ->getMock();
        $utilsMock->expects($this->once())
            ->method('getObjectIdFromUrl')
            ->with('inputUrl')
            ->will($this->returnValue('outputUrl'));
        $baseHelper  = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $nodeConfig  = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper  = new EduSharingAuthHelper($baseHelper);
        $nodeHelper  = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['createUsage', 'getTicket'])
            ->setConstructorArgs([$authHelper, $nodeHelper, $utilsMock])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('createUsage')
            ->with($usageData)
            ->will($this->returnValue(new Usage('whatever', 'nodeVersionTest', 'whatever', 'whatever', '4')));
        $this->assertEquals(3, $serviceMock->addInstance($eduObject));
    }

    /**
     * Function testIfAddInstanceReturnsFalseAndResetsDataOnCreationFailure
     *
     * @return void
     *
     * @backupGlobals enabled
     */
    public function testIfAddInstanceReturnsFalseAndResetsDataOnCreationFailure(): void {
        require_once('lib/dml/tests/dml_test.php');
        $currentTime                        = time();
        $eduObject                          = new stdClass();
        $eduObject->object_url              = 'inputUrl';
        $eduObject->course                  = 'containerIdTest';
        $eduObject->object_version          = '1';
        $eduObject->id                      = 'resourceIdTest';
        $processedEduObject                 = clone($eduObject);
        $processedEduObject->object_version = '';
        $processedEduObject->timecreated    = $currentTime;
        $processedEduObject->timemodified   = $currentTime;
        $processedEduObject->timeupdated    = $currentTime;
        $processedEduObject->options        = '';
        $processedEduObject->popup_window   = '';
        $processedEduObject->tracking       = 0;
        $insertedEduObject                  = clone($processedEduObject);
        $insertedEduObject->id              = 3;
        $insertedEduObject->usage_id        = 4;
        $insertedEduObject->object_version  = 'nodeVersionTest';
        $usageData                          = new stdClass();
        $usageData->containerId             = 'containerIdTest';
        $usageData->resourceId              = 3;
        $usageData->nodeId                  = 'outputUrl';
        $usageData->nodeVersion             = '';
        $dbMock                             = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['insert_record', 'update_record', 'delete_records'])
            ->getMock();
        $dbMock->expects($this->once())
            ->method('insert_record')
            ->with('edusharing', $processedEduObject)
            ->will($this->returnValue(3));
        $dbMock->expects($this->never())
            ->method('update_record');
        $dbMock->expects($this->once())
            ->method('delete_records')
            ->with('edusharing', ['id' => 3]);
        $GLOBALS['DB'] = $dbMock;
        $utilsMock     = $this->getMockBuilder(UtilityFunctions::class)
            ->onlyMethods(['getObjectIdFromUrl'])
            ->getMock();
        $utilsMock->expects($this->once())
            ->method('getObjectIdFromUrl')
            ->with('inputUrl')
            ->will($this->returnValue('outputUrl'));
        $baseHelper  = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $nodeConfig  = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper  = new EduSharingAuthHelper($baseHelper);
        $nodeHelper  = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['createUsage', 'getTicket'])
            ->setConstructorArgs([$authHelper, $nodeHelper, $utilsMock])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('createUsage')
            ->with($usageData)
            ->willThrowException(new Exception(''));
        $this->assertEquals(false, $serviceMock->addInstance($eduObject));
    }

    /**
     * Function testIfDeleteUsageThrowsExceptionIfProvidedObjectHasNoUsageId
     *
     * @return void
     * @throws dml_exception
     */
    public function testIfDeleteUsageThrowsExceptionIfProvidedObjectHasNoUsageId(): void {
        $usageObject         = new stdClass();
        $usageObject->nodeId = 'nodeIdTest';
        $baseHelper          = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $nodeConfig          = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper          = new EduSharingAuthHelper($baseHelper);
        $nodeHelperMock      = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->onlyMethods(['deleteUsage'])
            ->setConstructorArgs([$baseHelper, $nodeConfig])
            ->getMock();
        $nodeHelperMock->expects($this->never())
            ->method('deleteUsage');
        $service = new EduSharingService($authHelper, $nodeHelperMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No usage id provided, deletion cannot be performed');
        $service->deleteUsage($usageObject);
    }

    /**
     * Function testIfDeleteInstanceCallsDatabaseWithProperParams
     *
     * @backupGlobals enabled
     * @return void
     * @throws dml_exception
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
            ->with('edusharing', ['id' => $id], '*', MUST_EXIST)
            ->will($this->returnValue($dbRecord));
        $dbMock->expects($this->once())
            ->method('delete_records')
            ->with('edusharing', ['id' => 'edusharingId123']);
        $GLOBALS['DB'] = $dbMock;
        $baseHelper    = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper    = new EduSharingAuthHelper($baseHelper);
        $nodeConfig    = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper    = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $utilsMock     = $this->getMockBuilder(UtilityFunctions::class)
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
        $serviceMock->deleteInstance((string)$id);
    }

    /**
     * Function testIfImportMetadataCallsCurlWithTheCorrectParams
     *
     * @backupGlobals enabled
     * @return void
     * @throws dml_exception
     */
    public function testIfImportMetadataCallsCurlWithTheCorrectParams(): void {
        global $_SERVER;
        $_SERVER['HTTP_USER_AGENT'] = 'testAgent';
        $url                        = 'http://test.de';
        $expectedOptions            = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER         => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT      => 'testAgent'
        ];
        $curl                       = new CurlResult('testContent', 0, []);
        $baseMock                   = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs(['www.url.de', 'pkey123', 'appid123'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->with($url, $expectedOptions)
            ->will($this->returnValue($curl));
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper = new EduSharingAuthHelper($baseMock);
        $nodeHelper = new EduSharingNodeHelper($baseMock, $nodeConfig);
        $service    = new EduSharingService($authHelper, $nodeHelper);
        $this->assertEquals($curl, $service->importMetadata($url));
    }

    /**
     * Function testIfValidateSessionCallsCurlWithTheCorrectParams
     *
     * @return void
     * @throws dml_exception
     */
    public function testIfValidateSessionCallsCurlWithTheCorrectParams(): void {
        $url             = 'http://test.de';
        $headers         = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode('testAuth')
        ];
        $expectedOptions = [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers
        ];
        $curl            = new CurlResult('testContent', 0, []);
        $baseMock        = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs(['www.url.de', 'pkey123', 'appid123'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->with($url . 'rest/authentication/v1/validateSession', $expectedOptions)
            ->will($this->returnValue($curl));
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper = new EduSharingAuthHelper($baseMock);
        $nodeHelper = new EduSharingNodeHelper($baseMock, $nodeConfig);
        $service    = new EduSharingService($authHelper, $nodeHelper);
        $this->assertEquals($curl, $service->validateSession($url, 'testAuth'));
    }

    /**
     * Function testIfRegisterPluginCallsCurlWithTheCorrectOptions
     *
     * @return void
     * @throws dml_exception
     */
    public function testIfRegisterPluginCallsCurlWithTheCorrectOptions(): void {
        $url         = 'http://test.de';
        $delimiter   = 'delimiterTest';
        $body        = 'bodyTest';
        $auth        = 'authTest';
        $headers     = [
            'Content-Type: multipart/form-data; boundary=' . $delimiter,
            'Content-Length: ' . strlen($body),
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($auth)
        ];
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body
        ];
        $curl        = new CurlResult('testContent', 0, []);
        $baseHelper  = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $curlMock    = $this->getMockBuilder(MoodleCurlHandler::class)
            ->onlyMethods(['handleCurlRequest', 'setMethod'])
            ->getMock();
        $curlMock->expects($this->once())
            ->method('setMethod')
            ->with(EdusharingCurlHandler::METHOD_PUT);
        $curlMock->expects($this->once())
            ->method('handleCurlRequest')
            ->with($url . 'rest/admin/v1/applications/xml', $curlOptions)
            ->will($this->returnValue($curl));
        $baseHelper->registerCurlHandler($curlMock);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $service    = new EduSharingService($authHelper, $nodeHelper);
        $this->assertEquals($curl, $service->registerPlugin($url, $delimiter, $body, $auth));
    }

    /**
     * Function testIfSignCallsBaseHelperMethodWithCorrectParams
     *
     * @return void
     * @throws dml_exception
     */
    public function testIfSignCallsBaseHelperMethodWithCorrectParamsAndReturnsItsReturnedValue(): void {
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs(['www.url.de', 'pkey123', 'appid123'])
            ->onlyMethods(['sign'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('sign')
            ->with('testInput')
            ->will($this->returnValue('testOutput'));
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper = new EduSharingAuthHelper($baseMock);
        $nodeHelper = new EduSharingNodeHelper($baseMock, $nodeConfig);
        $service    = new EduSharingService($authHelper, $nodeHelper);
        $this->assertEquals('testOutput', $service->sign('testInput'));
    }

    /**
     * Function testGetRenderHtmlCallsCurlHandlerWithCorrectParamsAndReturnsContentOnSuccess
     *
     * @return void
     * @throws dml_exception
     *
     * @backupGlobals enabled
     */
    function testGetRenderHtmlCallsCurlHandlerWithCorrectParamsAndReturnsContentOnSuccess(): void {
        global $_SERVER;
        $_SERVER['HTTP_USER_AGENT'] = 'testAgent';
        $baseHelper                 = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $curlOptions                = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER         => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT']
        ];
        $curlMock                   = $this->getMockBuilder(MoodleCurlHandler::class)
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $curlMock->expects($this->once())
            ->method('handleCurlRequest')
            ->with('www.testUrl.de', $curlOptions)
            ->will($this->returnValue(new CurlResult('expectedContent', 0, [])));
        $baseHelper->registerCurlHandler($curlMock);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $service    = new EduSharingService($authHelper, $nodeHelper);
        $this->assertEquals('expectedContent', $service->getRenderHtml('www.testUrl.de'));
    }

    /**
     * Function testGetRenderHtmlReturnsErrorMessageIfCurlResultHasError
     *
     * @return void
     * @throws dml_exception
     *
     * @backupGlobals enabled
     */
    function testGetRenderHtmlReturnsErrorMessageIfCurlResultHasError(): void {
        global $_SERVER;
        $_SERVER['HTTP_USER_AGENT'] = 'testAgent';
        $baseHelper                 = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $curlOptions                = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER         => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT']
        ];
        $curlMock                   = $this->getMockBuilder(MoodleCurlHandler::class)
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $curlMock->expects($this->once())
            ->method('handleCurlRequest')
            ->with('www.testUrl.de', $curlOptions)
            ->will($this->returnValue(new CurlResult('expectedContent', 1, ['message' => 'error'])));
        $baseHelper->registerCurlHandler($curlMock);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $service    = new EduSharingService($authHelper, $nodeHelper);
        $this->assertEquals('Unexpected Error', $service->getRenderHtml('www.testUrl.de'));
    }
}
