<?php declare(strict_types=1);

use EduSharingApiClient\CurlResult;
use EduSharingApiClient\EduSharingAuthHelper;
use EduSharingApiClient\EduSharingHelperBase;
use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\EduSharingNodeHelperConfig;
use EduSharingApiClient\UrlHandling;
use mod_edusharing\EduSharingService;
use mod_edusharing\EduSharingUserException;
use mod_edusharing\MetadataLogic;
use mod_edusharing\UtilityFunctions;
use testUtils\FakeConfig;

/**
 * Class MetadataLogicTest
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
class MetadataLogicTest extends advanced_testcase
{
    /**
     * Function testIfImportMetadataSetsAllConfigEntriesOnSuccess
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     *
     * @backupGlobals enabled
     */
    public function testIfImportMetadataSetsAllConfigEntriesOnSuccess(): void {
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $metadataUrl            = 'test.de';
        $metadataXml            = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper             = new EduSharingAuthHelper($baseHelper);
        $nodeConfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper             = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $fakeConfig             = new FakeConfig();
        $fakeConfig->setEntries([
            'application_appid' => 'app123'
        ]);
        $utils       = new UtilityFunctions($fakeConfig);
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $logic = new MetadataLogic($serviceMock, $utils);
        $logic->importMetadata($metadataUrl);
        $this->assertEquals('http', $fakeConfig->get('repository_clientprotocol'));
        $this->assertEquals('http://test.de/edu-sharing/services/authbyapp', $fakeConfig->get('repository_authenticationwebservice'));
        $this->assertEquals('http://test.de/edu-sharing/services/usage2', $fakeConfig->get('repository_usagewebservice'));
        $this->assertEquals('publicKeyTest', $fakeConfig->get('repository_public_key'));
        $this->assertEquals('http://test.de/esrender/application/esmain/index.php', $fakeConfig->get('repository_contenturl'));
        $this->assertEquals('local', $fakeConfig->get('repository_appcaption'));
        $this->assertEquals('8100', $fakeConfig->get('repository_clientport'));
        $this->assertEquals('8080', $fakeConfig->get('repository_port'));
        $this->assertEquals('test.de', $fakeConfig->get('repository_domain'));
        $this->assertEquals('http://test.de/edu-sharing/services/authbyapp?wsdl', $fakeConfig->get('repository_authenticationwebservice_wsdl'));
        $this->assertEquals('REPOSITORY', $fakeConfig->get('repository_type'));
        $this->assertEquals('enterprise-docker-maven-fixes-8-0', $fakeConfig->get('repository_appid'));
        $this->assertEquals('http:/test.de/edu-sharing/services/usage2?wsdl', $fakeConfig->get('repository_usagewebservice_wsdl'));
        $this->assertEquals('http', $fakeConfig->get('repository_protocol'));
        $this->assertEquals('repository-service', $fakeConfig->get('repository_host'));
    }

    /**
     * Function testIfImportMetadataGeneratesNewAppIdIfNonePresent
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     *
     * @backupGlobals enabled
     */
    public function testIfImportMetadataGeneratesNewAppIdIfNonePresent(): void {
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $fakeConfig             = new FakeConfig();
        $metadataUrl            = 'test.de';
        $metadataXml            = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper             = new EduSharingAuthHelper($baseHelper);
        $nodeConfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper             = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $utils                  = new UtilityFunctions($fakeConfig);
        $serviceMock            = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $logic = new MetadataLogic($serviceMock, $utils);
        $logic->importMetadata($metadataUrl);
        $this->assertTrue(is_string($fakeConfig->get('application_appid')), 'application_appid was not set');
        $this->assertTrue(str_contains($fakeConfig->get('application_appid'), 'moodle_'), 'application_appid does not contain moodle prefix');
    }

    /**
     * Function testIfImportMetadataUsesConfiguredAppIdIfFound
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     *
     * @backupGlobals enabled
     */
    public function testIfImportMetadataUsesConfiguredAppIdIfFound(): void {
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $fakeConfig             = new FakeConfig();
        $fakeConfig->setEntries([
            'application_appid' => 'testId'
        ]);
        $metadataUrl = 'test.de';
        $metadataXml = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper  = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper  = new EduSharingAuthHelper($baseHelper);
        $nodeConfig  = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper  = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $utils = new UtilityFunctions($fakeConfig);
        $logic = new MetadataLogic($serviceMock, $utils);
        $logic->importMetadata($metadataUrl);
        $this->assertEquals('testId', $fakeConfig->get('application_appid'));
    }

    /**
     * Function testIfImportMetadataUsesAppIdClassVariableIfSet
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     *
     * @backupGlobals enabled
     */
    public function testIfImportMetadataUsesAppIdClassVariableIfSet(): void {
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $metadataUrl            = 'test.de';
        $metadataXml            = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper             = new EduSharingAuthHelper($baseHelper);
        $nodeConfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper             = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock            = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $fakeConfig = new FakeConfig();
        $utils      = new UtilityFunctions($fakeConfig);
        $logic      = new MetadataLogic($serviceMock, $utils);
        $logic->setAppId('testId');
        $logic->importMetadata($metadataUrl);
        $this->assertEquals('testId', $fakeConfig->get('application_appid'));
    }

    /**
     * Function testIfImportMetadataDoesNotSetHostAliasesIfNoneAreSet
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     *
     * @backupGlobals enabled
     **/
    public function testIfImportMetadataDoesNotSetHostAliasesIfNoneAreSet(): void {
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $metadataUrl            = 'test.de';
        $metadataXml            = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper             = new EduSharingAuthHelper($baseHelper);
        $nodeConfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper             = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock            = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $fakeConfig = new FakeConfig();
        $fakeConfig->setEntries([
            'application_appid' => 'testId'
        ]);
        $utils = new UtilityFunctions($fakeConfig);
        $logic = new MetadataLogic($serviceMock, $utils);
        $logic->importMetadata($metadataUrl);
        $this->assertFalse($fakeConfig->get('application_host_aliases'));
    }

    /**
     * Function testIfImportMetadataSetsHostAliasesIfSetAsClassVariables
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     *
     * @backupGlobals enabled
     **/
    public function testIfImportMetadataSetsHostAliasesIfSetAsClassVariables(): void {
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $metadataUrl            = 'test.de';
        $metadataXml            = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper             = new EduSharingAuthHelper($baseHelper);
        $nodeConfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper             = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock            = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $fakeConfig = new FakeConfig();
        $fakeConfig->setEntries([
            'application_appid' => 'testId'
        ]);
        $utils = new UtilityFunctions($fakeConfig);
        $logic = new MetadataLogic($serviceMock, $utils);
        $logic->setHostAliases('hostAliasesTest');
        $logic->importMetadata($metadataUrl);
        $this->assertEquals('hostAliasesTest', $fakeConfig->get('application_host_aliases'));
    }

    /**
     * Function testIfImportMetadataDoesNotSetWloGuestUserIfNoneProvided
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     *
     * @backupGlobals enabled
     **/
    public function testIfImportMetadataDoesNotSetWloGuestUserIfNoneProvided(): void {
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $metadataUrl            = 'test.de';
        $metadataXml            = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper             = new EduSharingAuthHelper($baseHelper);
        $nodeConfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper             = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock            = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $fakeConfig = new FakeConfig();
        $fakeConfig->setEntries([
            'application_appid' => 'testId'
        ]);
        $utils = new UtilityFunctions($fakeConfig);
        $logic = new MetadataLogic($serviceMock, $utils);
        $logic->importMetadata($metadataUrl);
        $this->assertFalse($fakeConfig->get('edu_guest_guest_id'));
        $this->assertFalse($fakeConfig->get('wlo_guest_option'));
    }

    /**
     * Function testIfImportMetadataDoesSetWloGuestUserIfClassVariableIsSet
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     *
     * @backupGlobals enabled
     **/
    public function testIfImportMetadataDoesSetWloGuestUserIfClassVariableIsSet(): void {
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $metadataUrl            = 'test.de';
        $metadataXml            = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper             = new EduSharingAuthHelper($baseHelper);
        $nodeConfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper             = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock            = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $fakeConfig = new FakeConfig();
        $fakeConfig->setEntries([
            'application_appid' => 'testId'
        ]);
        $utils = new UtilityFunctions($fakeConfig);
        $logic = new MetadataLogic($serviceMock, $utils);
        $logic->setWloGuestUser('wloGuestTest');
        $logic->importMetadata($metadataUrl);
        $this->assertEquals('wloGuestTest', $fakeConfig->get('edu_guest_guest_id'));
        $this->assertEquals('1', $fakeConfig->get('wlo_guest_option'));
    }

    /**
     * Function testIfImportMetadataGeneratesNewKeyPairIfNoneFound
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     *
     * @backupGlobals enabled
     **/
    public function testIfImportMetadataGeneratesNewKeyPairIfNoneFound(): void {
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $metadataUrl            = 'test.de';
        $metadataXml            = file_get_contents(__DIR__ . '/metadataTestWithoutKey.xml');
        $baseHelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper             = new EduSharingAuthHelper($baseHelper);
        $nodeConfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper             = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock            = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $fakeConfig = new FakeConfig();
        $fakeConfig->setEntries([
            'application_appid' => 'testId'
        ]);
        $utils = new UtilityFunctions($fakeConfig);
        $logic = new MetadataLogic($serviceMock, $utils);
        $logic->setWloGuestUser('wloGuestTest');
        $logic->importMetadata($metadataUrl);
        $this->assertNotEmpty($fakeConfig->get('application_private_key'));
        $this->assertNotEmpty($fakeConfig->get('application_public_key'));
    }

    /**
     * Function testIfCreateXmlMetadataCreatesXmlWithAllNeededEntries
     *
     * @return void
     *
     * @backupGlobals enabled
     *
     * @throws dml_exception
     */
    public function testIfCreateXmlMetadataCreatesXmlWithAllNeededEntries(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $CFG->wwwroot = 'https://www.example.com/moodle';
        $fakeConfig   = new FakeConfig();
        $fakeConfig->setEntries([
            'application_appid'         => 'testAppId',
            'application_type'          => 'testType',
            'application_host'          => 'testHost',
            'application_host_aliases'  => 'testHostAliases',
            'application_public_key'    => 'testPublicKey',
            'EDU_AUTH_AFFILIATION_NAME' => 'testAffiliationName',
            'edu_guest_guest_id'        => 'testGuestId',
            'wlo_guest_option'          => '1'
        ]);
        $baseHelper = new EduSharingHelperBase('www.url.de', 'testPublicKey', 'testAppId');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $logic      = new MetadataLogic(new EduSharingService($authHelper, $nodeHelper), new UtilityFunctions($fakeConfig));
        $xmlString  = $logic->createXmlMetadata();
        $xml        = new SimpleXMLElement($xmlString);
        $this->assertEquals(11, $xml->count());
        $this->assertEquals('testAppId', $xml->xpath('entry[@key="appid"]')[0]);
        $this->assertEquals('testType', $xml->xpath('entry[@key="type"]')[0]);
        $this->assertEquals('moodle', $xml->xpath('entry[@key="subtype"]')[0]);
        $this->assertEquals('www.example.com', $xml->xpath('entry[@key="domain"]')[0]);
        $this->assertEquals('testHost', $xml->xpath('entry[@key="host"]')[0]);
        $this->assertEquals('testHostAliases', $xml->xpath('entry[@key="host_aliases"]')[0]);
        $this->assertEquals('true', $xml->xpath('entry[@key="trustedclient"]')[0]);
        $this->assertEquals('moodle:course/update', $xml->xpath('entry[@key="hasTeachingPermission"]')[0]);
        $this->assertEquals('testPublicKey', $xml->xpath('entry[@key="public_key"]')[0]);
        $this->assertEquals('testAffiliationName', $xml->xpath('entry[@key="appcaption"]')[0]);
        $this->assertEquals('testGuestId', $xml->xpath('entry[@key="auth_by_app_user_whitelist"]')[0]);
    }
}
