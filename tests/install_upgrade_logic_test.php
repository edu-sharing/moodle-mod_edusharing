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
use EduSharingApiClient\EduSharingAuthHelper;
use EduSharingApiClient\EduSharingHelperBase;
use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\EduSharingNodeHelperConfig;
use EduSharingApiClient\UrlHandling;
use Exception;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class InstallUpgradeLogicTest
 *
 * @author Marian Ziegler
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\mod_edusharing\InstallUpgradeLogic::class)]
final class install_upgrade_logic_test extends advanced_testcase {
    /**
     * Function test_parse_config_data_throws_exception_if_file_not_found
     *
     * @return void
     * @throws JsonException
     */
    public function test_parse_config_data_throws_exception_if_file_not_found(): void {
        $logic = new InstallUpgradeLogic(__DIR__ . '/../nothing/tests/installConfigTest.json');
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing installConfig');
        $logic->parse_config_data();
    }

    /**
     * Function test_parse_config_data_throws_json_exception_if_json_invalid
     *
     * @return void
     * @throws JsonException
     */
    public function test_parse_config_data_throws_json_exception_if_json_invalid(): void {
        $logic = new InstallUpgradeLogic(__DIR__ . '/../tests/installConfigTestInvalid.json');
        $this->expectException(JsonException::class);
        $logic->parse_config_data();
    }

    /**
     * Function test_perform_returns_void_if_all_goes_well
     *
     * @return void
     * @throws JsonException
     * @throws dml_exception
     */
    public function test_perform_returns_void_if_all_goes_well(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
        $basehelper        = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $authhelper        = new EduSharingAuthHelper($basehelper);
        $nodeconfig        = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper        = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $service           = new EduSharingService($authhelper, $nodehelper);
        $metadatalogicmock = $this->getMockBuilder(MetadataLogic::class)
            ->setConstructorArgs([$service])
            ->onlyMethods(['import_metadata', 'create_xml_metadata'])
            ->getMock();
        $registrationlogicmock = $this->getMockBuilder(PluginRegistration::class)
            ->setConstructorArgs([$service])
            ->onlyMethods(['register_plugin'])
            ->getMock();
        $metadatalogicmock->expects($this->once())
            ->method('import_metadata')
            ->with('http://localhost:8080/edu-sharing/metadata?format=lms&external=true');
        $metadatalogicmock->expects($this->once())
            ->method('create_xml_metadata')
            ->willReturn('superTestData');
        $registrationlogicmock->expects($this->once())
            ->method('register_plugin')
            ->willReturn(['appid' => 'testId']);
        $logic = new InstallUpgradeLogic(__DIR__ . '/../tests/installConfigTest.json');
        $logic->set_registration_logic($registrationlogicmock);
        $logic->set_metadata_logic($metadatalogicmock);
        $logic->parse_config_data();
        $logic->perform();
    }
}
