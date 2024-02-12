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

/**
 * install.php
 *
 * Performed on every plugin installation
 * Checks for settings in installConfig.json
 * imports metadata and registers plugin with provided data
 *
 * @package mod_edusharing
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use EduSharingApiClient\EduSharingAuthHelper;
use EduSharingApiClient\EduSharingHelperBase;
use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\EduSharingNodeHelperConfig;
use EduSharingApiClient\UrlHandling;
use mod_edusharing\EduSharingService;
use mod_edusharing\InstallUpgradeLogic;
use mod_edusharing\MetadataLogic;
use mod_edusharing\MoodleCurlHandler;
use mod_edusharing\PluginRegistration;
use mod_edusharing\UtilityFunctions;

/**
 * Function xmldb_edusharing_install
 *
 * @return void
 */
function xmldb_edusharing_install(): void {
    global $CFG;
    require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
    $logic = new InstallUpgradeLogic();
    try {
        $logic->parse_config_data();
        $appid = $logic->discern_app_id();
        $data  = $logic->get_config_data();
        $utils = new UtilityFunctions();
        $utils->set_config_entry('application_appid', $appid);
        $utils->set_config_entry('send_additional_auth', '1');
        if (empty($data['repoUrl']) || empty($data['repoAdmin']) || empty($data['repoAdminPassword'])) {
            return;
        }
        $basehelper = new EduSharingHelperBase($data['repoUrl'], '', $appid);
        $basehelper->registerCurlHandler(new MoodleCurlHandler());
        $authhelper = new EduSharingAuthHelper($basehelper);
        $nodeconfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodehelper = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $service    = new EduSharingService($authhelper, $nodehelper);
        $logic->set_registration_logic(new PluginRegistration($service));
        $metadatalogic = new MetadataLogic($service);
        $metadatalogic->set_app_id($appid);
        $logic->set_metadata_logic($metadatalogic);
        $logic->perform();
    } catch (Exception $exception) {
        debugging(($exception instanceof JsonException
                ? 'Metadata import and plugin registration failed, invalid installConfig.json: ' : '') . $exception->getMessage());
        return;
    }
}
