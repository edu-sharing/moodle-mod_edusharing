<?php declare(strict_types = 1);

use EduSharingApiClient\CurlResult;
use EduSharingApiClient\EduSharingAuthHelper;
use EduSharingApiClient\EduSharingHelperBase;
use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\EduSharingNodeHelperConfig;
use EduSharingApiClient\UrlHandling;
use mod_edusharing\EduSharingService;
use mod_edusharing\EduSharingUserException;
use mod_edusharing\PluginRegistration;

/**
 * class PluginRegistrationTest
 */
class PluginRegistrationTest extends advanced_testcase
{
    /**
     * Function testRegisterPluginReturnsContentFromServiceCallOnSuccess
     *
     * @return void
     * @throws EduSharingUserException
     * @throws JsonException
     */
    public function testRegisterPluginReturnsContentFromServiceCallOnSuccess(): void {
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $repoUrl = 'http://test.de';
        $user = 'uName';
        $password = 'testPass';
        $data = 'data';
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['validateSession', 'registerPlugin'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('validateSession')
            ->with($repoUrl, $user . ':' . $password)
            ->will($this->returnValue(new CurlResult('{"isAdmin": true}', 0, [])));
        $serviceMock->expects($this->once())
            ->method('registerPlugin')
            ->with($repoUrl, $this->anything(), $this->anything(), $user . ':' . $password)
            ->will($this->returnValue(new CurlResult('{"content": "expectedContent"}', 0, [])));
        $registrationLogic = new PluginRegistration($serviceMock);
        $result = $registrationLogic->registerPlugin($repoUrl, $user, $password, $data);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals('expectedContent', $result['content']);
    }

    /**
     * Function testRegisterPluginThrowsApiConnectionExceptionWhenValidateSessionFailsWithError
     *
     * @return void
     * @throws EduSharingUserException
     * @throws JsonException
     */
    public function testRegisterPluginThrowsApiConnectionExceptionWhenValidateSessionFailsWithError(): void {
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $repoUrl = 'http://test.de';
        $user = 'uName';
        $password = 'testPass';
        $data = 'data';
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['validateSession', 'registerPlugin'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('validateSession')
            ->with($repoUrl, $user . ':' . $password)
            ->will($this->returnValue(new CurlResult('{"isAdmin": true}', 3, [])));
        $registrationLogic = new PluginRegistration($serviceMock);
        $this->expectException(EduSharingUserException::class);
        $this->expectExceptionMessage('API connection error');
        $registrationLogic->registerPlugin($repoUrl, $user, $password, $data);
    }


    /**
     * Function testRegisterPluginThrowsInvalidCredentialsExceptionIfUserIsNoAdmin
     *
     * @return void
     * @throws EduSharingUserException
     * @throws JsonException
     */
    public function testRegisterPluginThrowsInvalidCredentialsExceptionIfUserIsNoAdmin(): void {
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $repoUrl = 'http://test.de';
        $user = 'uName';
        $password = 'testPass';
        $data = 'data';
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['validateSession', 'registerPlugin'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('validateSession')
            ->with($repoUrl, $user . ':' . $password)
            ->will($this->returnValue(new CurlResult('{"isAdmin": false}', 0, [])));
        $registrationLogic = new PluginRegistration($serviceMock);
        $this->expectException(EduSharingUserException::class);
        $this->expectExceptionMessage('Given user / password was not accepted as admin');
        $registrationLogic->registerPlugin($repoUrl, $user, $password, $data);
    }

    /**
     * Function testRegisterPluginThrowsApiConnectionExceptionWhenRegisterPluginFailsWithError
     *
     * @return void
     * @throws EduSharingUserException
     * @throws JsonException
     */
    public function testRegisterPluginThrowsApiConnectionExceptionWhenRegisterPluginFailsWithError(): void {
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $repoUrl = 'http://test.de';
        $user = 'uName';
        $password = 'testPass';
        $data = 'data';
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['validateSession', 'registerPlugin'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('validateSession')
            ->with($repoUrl, $user . ':' . $password)
            ->will($this->returnValue(new CurlResult('{"isAdmin": true}', 0, [])));
        $serviceMock->expects($this->once())
            ->method('registerPlugin')
            ->with($repoUrl, $this->anything(), $this->anything(), $user . ':' . $password)
            ->will($this->returnValue(new CurlResult('{"content": "expectedContent"}', 1, [])));
        $registrationLogic = new PluginRegistration($serviceMock);
        $this->expectException(EduSharingUserException::class);
        $this->expectExceptionMessage('API connection error');
        $registrationLogic->registerPlugin($repoUrl, $user, $password, $data);
    }

    /**
     * Function testRegisterPluginThrowsJsonExceptionWithInvalidJsonReturnedFromApi
     *
     * @return void
     * @throws EduSharingUserException
     * @throws JsonException
     */
    public function testRegisterPluginThrowsJsonExceptionWithInvalidJsonReturnedFromApi(): void {
        $baseHelper = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $repoUrl = 'http://test.de';
        $user = 'uName';
        $password = 'testPass';
        $data = 'data';
        $serviceMock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['validateSession', 'registerPlugin'])
            ->setConstructorArgs([$authHelper, $nodeHelper])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('validateSession')
            ->with($repoUrl, $user . ':' . $password)
            ->will($this->returnValue(new CurlResult('{"isAdmin: false}', 0, [])));
        $registrationLogic = new PluginRegistration($serviceMock);
        $this->expectException(JsonException::class);
        $registrationLogic->registerPlugin($repoUrl, $user, $password, $data);
    }
}