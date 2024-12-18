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

// Namespace does not match PSR. But Moodle likes it this way.
namespace mod_edusharing;

use advanced_testcase;
use EduSharingApiClient\CurlResult;
use EduSharingApiClient\EduSharingAuthHelper;
use EduSharingApiClient\EduSharingHelperBase;
use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\EduSharingNodeHelperConfig;
use EduSharingApiClient\UrlHandling;
use JsonException;

// phpcs:ignore -- No Moodle internal check needed.
global $CFG;
require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');

/**
 * class PluginRegistrationTest
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_edusharing\PluginRegistration
 */
final class plugin_registration_test extends advanced_testcase {
    /**
     * Function test_register_plugin_returns_content_from_service_call_on_success
     *
     * @return void
     * @throws EduSharingUserException
     * @throws JsonException
     */
    public function test_register_plugin_returns_content_from_service_call_on_success(): void {
        $basehelper  = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authhelper  = new EduSharingAuthHelper($basehelper);
        $nodeconfig  = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper  = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $repourl     = 'http://test.de';
        $user        = 'uName';
        $password    = 'testPass';
        $data        = 'data';
        $servicemock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['validate_session', 'register_plugin'])
            ->setConstructorArgs([$authhelper, $nodehelper])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('validate_session')
            ->with($repourl, $user . ':' . $password)
            ->will($this->returnValue(new CurlResult('{"isAdmin": true}', 0, [])));
        $servicemock->expects($this->once())
            ->method('register_plugin')
            ->with($repourl, $this->anything(), $this->anything(), $user . ':' . $password)
            ->will($this->returnValue(new CurlResult('{"content": "expectedContent"}', 0, [])));
        $registrationlogic = new PluginRegistration($servicemock);
        $result = $registrationlogic->register_plugin($repourl, $user, $password, $data);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals('expectedContent', $result['content']);
    }

    /**
     * Function test_register_plugin_throws_api_connection_exception_when_validate_session_fails_with_error
     *
     * @return void
     * @throws EduSharingUserException
     * @throws JsonException
     */
    public function test_register_plugin_throws_api_connection_exception_when_validate_session_fails_with_error(): void {
        $basehelper  = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authhelper  = new EduSharingAuthHelper($basehelper);
        $nodeconfig  = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper  = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $repourl     = 'http://test.de';
        $user        = 'uName';
        $password    = 'testPass';
        $data        = 'data';
        $servicemock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['validate_session', 'register_plugin'])
            ->setConstructorArgs([$authhelper, $nodehelper])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('validate_session')
            ->with($repourl, $user . ':' . $password)
            ->will($this->returnValue(new CurlResult('{"isAdmin": true}', 3, [])));
        $registrationlogic = new PluginRegistration($servicemock);
        $this->expectException(EduSharingUserException::class);
        $this->expectExceptionMessage('API connection error');
        $registrationlogic->register_plugin($repourl, $user, $password, $data);
    }


    /**
     * Function test_register_plugin_throws_invalid_credentials_exception_if_user_is_no_admin
     *
     * @return void
     * @throws EduSharingUserException
     * @throws JsonException
     */
    public function test_register_plugin_throws_invalid_credentials_exception_if_user_is_no_admin(): void {
        $basehelper  = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authhelper  = new EduSharingAuthHelper($basehelper);
        $nodeconfig  = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper  = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $repourl     = 'http://test.de';
        $user        = 'uName';
        $password    = 'testPass';
        $data        = 'data';
        $servicemock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['validate_session', 'register_plugin'])
            ->setConstructorArgs([$authhelper, $nodehelper])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('validate_session')
            ->with($repourl, $user . ':' . $password)
            ->will($this->returnValue(new CurlResult('{"isAdmin": false}', 0, [])));
        $registrationlogic = new PluginRegistration($servicemock);
        $this->expectException(EduSharingUserException::class);
        $this->expectExceptionMessage('Given user / password was not accepted as admin');
        $registrationlogic->register_plugin($repourl, $user, $password, $data);
    }

    /**
     * Function test_register_plugin_throws_api_connection_exception_when_register_plugin_fails_with_error
     *
     * @return void
     * @throws EduSharingUserException
     * @throws JsonException
     */
    public function test_register_plugin_throws_api_connection_exception_when_register_plugin_fails_with_error(): void {
        $basehelper  = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authhelper  = new EduSharingAuthHelper($basehelper);
        $nodeconfig  = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper  = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $repourl     = 'http://test.de';
        $user        = 'uName';
        $password    = 'testPass';
        $data        = 'data';
        $servicemock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['validate_session', 'register_plugin'])
            ->setConstructorArgs([$authhelper, $nodehelper])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('validate_session')
            ->with($repourl, $user . ':' . $password)
            ->will($this->returnValue(new CurlResult('{"isAdmin": true}', 0, [])));
        $servicemock->expects($this->once())
            ->method('register_plugin')
            ->with($repourl, $this->anything(), $this->anything(), $user . ':' . $password)
            ->will($this->returnValue(new CurlResult('{"content": "expectedContent"}', 1, [])));
        $registrationlogic = new PluginRegistration($servicemock);
        $this->expectException(EduSharingUserException::class);
        $this->expectExceptionMessage('API connection error');
        $registrationlogic->register_plugin($repourl, $user, $password, $data);
    }

    /**
     * Function test_register_plugin_throws_json_exception_with_invalid_json_returned_from_api
     *
     * @return void
     * @throws EduSharingUserException
     * @throws JsonException
     */
    public function test_register_plugin_throws_json_exception_with_invalid_json_returned_from_api(): void {
        $basehelper  = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authhelper  = new EduSharingAuthHelper($basehelper);
        $nodeconfig  = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper  = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $repourl     = 'http://test.de';
        $user        = 'uName';
        $password    = 'testPass';
        $data        = 'data';
        $servicemock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['validate_session', 'register_plugin'])
            ->setConstructorArgs([$authhelper, $nodehelper])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('validate_session')
            ->with($repourl, $user . ':' . $password)
            ->will($this->returnValue(new CurlResult('{"isAdmin: false}', 0, [])));
        $registrationlogic = new PluginRegistration($servicemock);
        $this->expectException(JsonException::class);
        $registrationlogic->register_plugin($repourl, $user, $password, $data);
    }
}
