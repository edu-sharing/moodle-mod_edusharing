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

declare(strict_types=1);

// Namespace does not match PSR. But Moodle likes it this way.
namespace mod_edusharing;

use advanced_testcase;
use dml_exception;
use EduSharingApiClient\CurlResult;
use EduSharingApiClient\EduSharingAuthHelper;
use EduSharingApiClient\EduSharingHelperBase;
use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\EduSharingNodeHelperConfig;
use EduSharingApiClient\UrlHandling;
use SimpleXMLElement;
use testUtils\FakeConfig;

// phpcs:ignore -- no Moodle internal check needed.
global $CFG;
require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');

/**
 * Class MetadataLogicTest
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @covers \mod_edusharing\MetadataLogic
 */
class metadata_logic_test extends advanced_testcase {
    /**
     * Function test_if_import_metadata_sets_all_config_entries_on_success
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     */
    public function test_if_import_metadata_sets_all_config_entries_on_success(): void {
        $this->resetAfterTest();
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $metadataurl            = 'test.de';
        $metadataxml            = file_get_contents(__DIR__ . '/metadataTest.xml');
        $basehelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authhelper             = new EduSharingAuthHelper($basehelper);
        $nodeconfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper             = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $fakeconfig             = new FakeConfig();
        $fakeconfig->set_entries([
            'application_appid' => 'app123',
        ]);
        $utils       = new UtilityFunctions($fakeconfig);
        $servicemock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['import_metadata'])
            ->setConstructorArgs([$authhelper, $nodehelper])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('import_metadata')
            ->with($metadataurl)
            ->will($this->returnValue(new CurlResult($metadataxml, 0, [])));
        $logic = new MetadataLogic($servicemock, $utils);
        $logic->import_metadata($metadataurl);
        $this->assertEquals('http', $fakeconfig->get('repository_clientprotocol'));
        $this->assertEquals('http://test.de/edu-sharing/services/authbyapp',
            $fakeconfig->get('repository_authenticationwebservice'));
        $this->assertEquals('http://test.de/edu-sharing/services/usage2', $fakeconfig->get('repository_usagewebservice'));
        $this->assertEquals('publicKeyTest', $fakeconfig->get('repository_public_key'));
        $this->assertEquals('http://test.de/esrender/application/esmain/index.php', $fakeconfig->get('repository_contenturl'));
        $this->assertEquals('local', $fakeconfig->get('repository_appcaption'));
        $this->assertEquals('8100', $fakeconfig->get('repository_clientport'));
        $this->assertEquals('8080', $fakeconfig->get('repository_port'));
        $this->assertEquals('test.de', $fakeconfig->get('repository_domain'));
        $this->assertEquals('http://test.de/edu-sharing/services/authbyapp?wsdl',
            $fakeconfig->get('repository_authenticationwebservice_wsdl'));
        $this->assertEquals('REPOSITORY', $fakeconfig->get('repository_type'));
        $this->assertEquals('enterprise-docker-maven-fixes-8-0', $fakeconfig->get('repository_appid'));
        $this->assertEquals('http:/test.de/edu-sharing/services/usage2?wsdl', $fakeconfig->get('repository_usagewebservice_wsdl'));
        $this->assertEquals('http', $fakeconfig->get('repository_protocol'));
        $this->assertEquals('repository-service', $fakeconfig->get('repository_host'));
    }

    /**
     * Function test_if_import_metadata_generates_new_app_id_if_none_present
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     */
    public function test_if_import_metadata_generates_new_app_id_if_none_present(): void {
        $this->resetAfterTest();
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $fakeconfig             = new FakeConfig();
        $metadataurl            = 'test.de';
        $metadataxml            = file_get_contents(__DIR__ . '/metadataTest.xml');
        $basehelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authhelper             = new EduSharingAuthHelper($basehelper);
        $nodeconfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper             = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $utils                  = new UtilityFunctions($fakeconfig);
        $servicemock            = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['import_metadata'])
            ->setConstructorArgs([$authhelper, $nodehelper])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('import_metadata')
            ->with($metadataurl)
            ->will($this->returnValue(new CurlResult($metadataxml, 0, [])));
        $logic = new MetadataLogic($servicemock, $utils);
        $logic->import_metadata($metadataurl);
        $this->assertTrue(is_string($fakeconfig->get('application_appid')), 'application_appid was not set');
        $this->assertTrue(str_contains($fakeconfig->get('application_appid'), 'moodle_'),
            'application_appid does not contain moodle prefix');
    }

    /**
     * Function test_if_import_metadata_uses_configured_app_id_if_found
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     */
    public function test_if_import_metadata_uses_configured_app_id_if_found(): void {
        $this->resetAfterTest();
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $fakeconfig             = new FakeConfig();
        $fakeconfig->set_entries([
            'application_appid' => 'testId',
        ]);
        $metadataurl = 'test.de';
        $metadataxml = file_get_contents(__DIR__ . '/metadataTest.xml');
        $basehelper  = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authhelper  = new EduSharingAuthHelper($basehelper);
        $nodeconfig  = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper  = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $servicemock = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['import_metadata'])
            ->setConstructorArgs([$authhelper, $nodehelper])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('import_metadata')
            ->with($metadataurl)
            ->will($this->returnValue(new CurlResult($metadataxml, 0, [])));
        $utils = new UtilityFunctions($fakeconfig);
        $logic = new MetadataLogic($servicemock, $utils);
        $logic->import_metadata($metadataurl);
        $this->assertEquals('testId', $fakeconfig->get('application_appid'));
    }

    /**
     * Function test_if_import_metadata_uses_app_id_class_variable_if_set
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     */
    public function test_if_import_metadata_uses_app_id_class_variable_if_set(): void {
        $this->resetAfterTest();
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $metadataurl            = 'test.de';
        $metadataxml            = file_get_contents(__DIR__ . '/metadataTest.xml');
        $basehelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authhelper             = new EduSharingAuthHelper($basehelper);
        $nodeconfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper             = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $servicemock            = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['import_metadata'])
            ->setConstructorArgs([$authhelper, $nodehelper])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('import_metadata')
            ->with($metadataurl)
            ->will($this->returnValue(new CurlResult($metadataxml, 0, [])));
        $fakeconfig = new FakeConfig();
        $utils      = new UtilityFunctions($fakeconfig);
        $logic      = new MetadataLogic($servicemock, $utils);
        $logic->set_app_id('testId');
        $logic->import_metadata($metadataurl);
        $this->assertEquals('testId', $fakeconfig->get('application_appid'));
    }

    /**
     * Function test_if_import_metadata_does_not_set_host_aliases_if_none_are_set
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     */
    public function test_if_import_metadata_does_not_set_host_aliases_if_none_are_set(): void {
        $this->resetAfterTest();
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $metadataurl            = 'test.de';
        $metadataxml            = file_get_contents(__DIR__ . '/metadataTest.xml');
        $basehelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authhelper             = new EduSharingAuthHelper($basehelper);
        $nodeconfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper             = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $servicemock            = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['import_metadata'])
            ->setConstructorArgs([$authhelper, $nodehelper])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('import_metadata')
            ->with($metadataurl)
            ->will($this->returnValue(new CurlResult($metadataxml, 0, [])));
        $fakeconfig = new FakeConfig();
        $fakeconfig->set_entries([
            'application_appid' => 'testId',
        ]);
        $utils = new UtilityFunctions($fakeconfig);
        $logic = new MetadataLogic($servicemock, $utils);
        $logic->import_metadata($metadataurl);
        $this->assertFalse($fakeconfig->get('application_host_aliases'));
    }

    /**
     * Function test_if_import_metadata_sets_host_aliases_if_set_as_class_variables
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     */
    public function test_if_import_metadata_sets_host_aliases_if_set_as_class_variables(): void {
        $this->resetAfterTest();
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $metadataurl            = 'test.de';
        $metadataxml            = file_get_contents(__DIR__ . '/metadataTest.xml');
        $basehelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authhelper             = new EduSharingAuthHelper($basehelper);
        $nodeconfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper             = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $servicemock            = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['import_metadata'])
            ->setConstructorArgs([$authhelper, $nodehelper])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('import_metadata')
            ->with($metadataurl)
            ->will($this->returnValue(new CurlResult($metadataxml, 0, [])));
        $fakeconfig = new FakeConfig();
        $fakeconfig->set_entries([
            'application_appid' => 'testId',
        ]);
        $utils = new UtilityFunctions($fakeconfig);
        $logic = new MetadataLogic($servicemock, $utils);
        $logic->set_host_aliases('hostAliasesTest');
        $logic->import_metadata($metadataurl);
        $this->assertEquals('hostAliasesTest', $fakeconfig->get('application_host_aliases'));
    }

    /**
     * Function test_if_import_metadata_does_not_set_wlo_guest_user_if_none_provided
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     **/
    public function test_if_import_metadata_does_not_set_wlo_guest_user_if_none_provided(): void {
        $this->resetAfterTest();
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $metadataurl            = 'test.de';
        $metadataxml            = file_get_contents(__DIR__ . '/metadataTest.xml');
        $basehelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authhelper             = new EduSharingAuthHelper($basehelper);
        $nodeconfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper             = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $servicemock            = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['import_metadata'])
            ->setConstructorArgs([$authhelper, $nodehelper])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('import_metadata')
            ->with($metadataurl)
            ->will($this->returnValue(new CurlResult($metadataxml, 0, [])));
        $fakeconfig = new FakeConfig();
        $fakeconfig->set_entries([
            'application_appid' => 'testId',
        ]);
        $utils = new UtilityFunctions($fakeconfig);
        $logic = new MetadataLogic($servicemock, $utils);
        $logic->import_metadata($metadataurl);
        $this->assertFalse($fakeconfig->get('edu_guest_guest_id'));
        $this->assertFalse($fakeconfig->get('wlo_guest_option'));
    }

    /**
     * Function test_if_import_metadata_does_set_wlo_guest_user_if_class_variable_is_set
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     **/
    public function test_if_import_metadata_does_set_wlo_guest_user_if_class_variable_is_set(): void {
        $this->resetAfterTest();
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $metadataurl            = 'test.de';
        $metadataxml            = file_get_contents(__DIR__ . '/metadataTest.xml');
        $basehelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authhelper             = new EduSharingAuthHelper($basehelper);
        $nodeconfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper             = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $servicemock            = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['import_metadata'])
            ->setConstructorArgs([$authhelper, $nodehelper])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('import_metadata')
            ->with($metadataurl)
            ->will($this->returnValue(new CurlResult($metadataxml, 0, [])));
        $fakeconfig = new FakeConfig();
        $fakeconfig->set_entries([
            'application_appid' => 'testId',
        ]);
        $utils = new UtilityFunctions($fakeconfig);
        $logic = new MetadataLogic($servicemock, $utils);
        $logic->set_wlo_guest_user('wloGuestTest');
        $logic->import_metadata($metadataurl);
        $this->assertEquals('wloGuestTest', $fakeconfig->get('edu_guest_guest_id'));
        $this->assertEquals('1', $fakeconfig->get('wlo_guest_option'));
    }

    /**
     * Function test_if_import_metadata_generates_new_key_pair_if_none_found
     *
     * @return void
     * @throws EduSharingUserException
     * @throws dml_exception
     **/
    public function test_if_import_metadata_generates_new_key_pair_if_none_found(): void {
        $this->resetAfterTest();
        global $_SERVER, $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $_SERVER['SERVER_NAME'] = 'testServer';
        $metadataurl            = 'test.de';
        $metadataxml            = file_get_contents(__DIR__ . '/metadataTestWithoutKey.xml');
        $basehelper             = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authhelper             = new EduSharingAuthHelper($basehelper);
        $nodeconfig             = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper             = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $servicemock            = $this->getMockBuilder(EduSharingService::class)
            ->onlyMethods(['import_metadata'])
            ->setConstructorArgs([$authhelper, $nodehelper])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('import_metadata')
            ->with($metadataurl)
            ->will($this->returnValue(new CurlResult($metadataxml, 0, [])));
        $fakeconfig = new FakeConfig();
        $fakeconfig->set_entries([
            'application_appid' => 'testId',
        ]);
        $utils = new UtilityFunctions($fakeconfig);
        $logic = new MetadataLogic($servicemock, $utils);
        $logic->set_wlo_guest_user('wloGuestTest');
        $logic->import_metadata($metadataurl);
        $this->assertNotEmpty($fakeconfig->get('application_private_key'));
        $this->assertNotEmpty($fakeconfig->get('application_public_key'));
    }

    /**
     * Function test_if_create_xml_metadata_creates_xml_with_all_needed_entries
     *
     * @return void
     *
     * @throws dml_exception
     */
    public function test_if_create_xml_metadata_creates_xml_with_all_needed_entries(): void {
        $this->resetAfterTest();
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        $CFG->wwwroot = 'https://www.example.com/moodle';
        $fakeconfig   = new FakeConfig();
        $fakeconfig->set_entries([
            'application_appid'         => 'testAppId',
            'application_type'          => 'testType',
            'application_host'          => 'testHost',
            'application_host_aliases'  => 'testHostAliases',
            'application_public_key'    => 'testPublicKey',
            'EDU_AUTH_AFFILIATION_NAME' => 'testAffiliationName',
            'edu_guest_guest_id'        => 'testGuestId',
            'wlo_guest_option'          => '1',
        ]);
        $basehelper = new EduSharingHelperBase('www.url.de', 'testPublicKey', 'testAppId');
        $authhelper = new EduSharingAuthHelper($basehelper);
        $nodeconfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $logic      = new MetadataLogic(new EduSharingService($authhelper, $nodehelper), new UtilityFunctions($fakeconfig));
        $xmlstring  = $logic->create_xml_metadata();
        $xml        = new SimpleXMLElement($xmlstring);
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
