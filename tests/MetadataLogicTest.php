<?php

use EduSharingApiClient\CurlResult;
use EduSharingApiClient\EduSharingAuthHelper;
use EduSharingApiClient\EduSharingHelperBase;
use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\EduSharingNodeHelperConfig;
use EduSharingApiClient\UrlHandling;
use mod_edusharing\EduSharingService;
use mod_edusharing\EduSharingUserException;
use mod_edusharing\MetadataLogic;

class MetadataLogicTest extends advanced_testcase
{
    /**
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     *
     * @backupGlobals enabled
     */
    public function testIfImportMetadataSetsAllConfigEntriesOnSuccess(): void {
        global $_SERVER;
        $_SERVER['SERVER_NAME'] = 'testServer';
        $this->resetAfterTest();
        $metadataUrl = 'test.de';
        $metadataXml = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $logic = new MetadataLogic($serviceMock);
        $logic->importMetadata($metadataUrl);
        $this->assertEquals('http', get_config('edusharing', 'repository_clientprotocol'));
        $this->assertEquals('http://test.de/edu-sharing/services/authbyapp', get_config('edusharing', 'repository_authenticationwebservice'));
        $this->assertEquals('http://test.de/edu-sharing/services/usage2', get_config('edusharing', 'repository_usagewebservice'));
        $this->assertEquals('publicKeyTest', get_config('edusharing', 'repository_public_key'));
        $this->assertEquals('http://test.de/esrender/application/esmain/index.php', get_config('edusharing', 'repository_contenturl'));
        $this->assertEquals('local', get_config('edusharing', 'repository_appcaption'));
        $this->assertEquals('8100', get_config('edusharing', 'repository_clientport'));
        $this->assertEquals('8080', get_config('edusharing', 'repository_port'));
        $this->assertEquals('test.de', get_config('edusharing', 'repository_domain'));
        $this->assertEquals('http://test.de/edu-sharing/services/authbyapp?wsdl', get_config('edusharing', 'repository_authenticationwebservice_wsdl'));
        $this->assertEquals('REPOSITORY', get_config('edusharing', 'repository_type'));
        $this->assertEquals('enterprise-docker-maven-fixes-8-0', get_config('edusharing', 'repository_appid'));
        $this->assertEquals('http:/test.de/edu-sharing/services/usage2?wsdl', get_config('edusharing', 'repository_usagewebservice_wsdl'));
        $this->assertEquals('http', get_config('edusharing', 'repository_protocol'));
        $this->assertEquals('repository-service', get_config('edusharing', 'repository_host'));
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
        global $_SERVER;
        $_SERVER['SERVER_NAME'] = 'testServer';
        $this->resetAfterTest();
        $metadataUrl = 'test.de';
        $appId = empty(get_config('edusharing', 'application_appid')) ? 'nothing' : get_config('edusharing', 'application_appid');
        $metadataXml = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $logic = new MetadataLogic($serviceMock);
        $logic->importMetadata($metadataUrl);
        $this->assertTrue(get_config('edusharing', 'application_appid') !== $appId);
        $this->assertTrue(str_contains(get_config('edusharing', 'application_appid'), 'moodle_'));
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
        global $_SERVER;
        $_SERVER['SERVER_NAME'] = 'testServer';
        set_config('application_appid', 'testId', 'edusharing');
        $this->resetAfterTest();
        $metadataUrl = 'test.de';
        $metadataXml = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $logic = new MetadataLogic($serviceMock);
        $logic->importMetadata($metadataUrl);
        $this->assertEquals('testId', get_config('edusharing', 'application_appid'));
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
        global $_SERVER;
        $_SERVER['SERVER_NAME'] = 'testServer';
        $this->resetAfterTest();
        $metadataUrl = 'test.de';
        $metadataXml = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $logic = new MetadataLogic($serviceMock);
        $logic->setAppId('testId');
        $logic->importMetadata($metadataUrl);
        $this->assertEquals('testId', get_config('edusharing', 'application_appid'));
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
        $this->resetAfterTest();
        global $_SERVER;
        $_SERVER['SERVER_NAME'] = 'testServer';
        unset_config('application_host_aliases', 'edusharing');
        $metadataUrl = 'test.de';
        $metadataXml = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $logic = new MetadataLogic($serviceMock);
        $logic->importMetadata($metadataUrl);
        $this->assertFalse(get_config('edusharing', 'application_host_aliases'));
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
        $this->resetAfterTest();
        global $_SERVER;
        $_SERVER['SERVER_NAME'] = 'testServer';
        unset_config('application_host_aliases', 'edusharing');
        $metadataUrl = 'test.de';
        $metadataXml = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $logic = new MetadataLogic($serviceMock);
        $logic->setHostAliases('hostAliasesTest');
        $logic->importMetadata($metadataUrl);
        $this->assertEquals('hostAliasesTest', get_config('edusharing', 'application_host_aliases'));
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
        $this->resetAfterTest();
        global $_SERVER;
        $_SERVER['SERVER_NAME'] = 'testServer';
        unset_config('edu_guest_guest_id', 'edusharing');
        unset_config('wlo_guest_option', 'edusharing');
        $metadataUrl = 'test.de';
        $metadataXml = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $logic = new MetadataLogic($serviceMock);
        $logic->importMetadata($metadataUrl);
        $this->assertFalse(get_config('edusharing', 'edu_guest_guest_id'));
        $this->assertFalse(get_config('edusharing', 'wlo_guest_option'));
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
        $this->resetAfterTest();
        global $_SERVER;
        $_SERVER['SERVER_NAME'] = 'testServer';
        unset_config('edu_guest_guest_id', 'edusharing');
        unset_config('wlo_guest_option', 'edusharing');
        $metadataUrl = 'test.de';
        $metadataXml = file_get_contents(__DIR__ . '/metadataTest.xml');
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $logic = new MetadataLogic($serviceMock);
        $logic->setWloGuestUser('wloGuestTest');
        $logic->importMetadata($metadataUrl);
        $this->assertEquals('wloGuestTest', get_config('edusharing', 'edu_guest_guest_id'));
        $this->assertEquals('1', get_config('edusharing', 'wlo_guest_option'));
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
        $this->resetAfterTest();
        global $_SERVER;
        $_SERVER['SERVER_NAME'] = 'testServer';
        unset_config('application_private_key', 'edusharing');
        unset_config('application_public_key', 'edusharing');
        $metadataUrl = 'test.de';
        $metadataXml = file_get_contents(__DIR__ . '/metadataTestWithoutKey.xml');
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['importMetadata'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('importMetadata')
            ->with($metadataUrl)
            ->will($this->returnValue(new CurlResult($metadataXml, 0, [])));
        $logic = new MetadataLogic($serviceMock);
        $logic->setWloGuestUser('wloGuestTest');
        $logic->importMetadata($metadataUrl);
        $this->assertNotEmpty(get_config('edusharing', 'application_private_key'));
        $this->assertNotEmpty(get_config('edusharing', 'application_public_key'));
    }
}