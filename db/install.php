<?php

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

defined('MOODLE_INTERNAL') || die();

function xmldb_edusharing_install(): void {
    global $CFG;
    require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
    $logic = new InstallUpgradeLogic();
    try {
        $logic->parse_config_data();
        $appId = $logic->discern_app_id();
        $data  = $logic->get_config_data();
        $utils = new UtilityFunctions();
        $utils->set_config_entry('application_appid', $appId);
        $utils->set_config_entry('send_additional_auth', '1');
        if (empty($data['repoUrl']) || empty($data['repoAdmin']) || empty($data['repoAdminPassword'])) {
            return;
        }
        $baseHelper = new EduSharingHelperBase($data['repoUrl'], '', $appId);
        $baseHelper->registerCurlHandler(new MoodleCurlHandler());
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $service    = new EduSharingService($authHelper, $nodeHelper);
        $logic->set_registration_logic(new PluginRegistration($service));
        $metadataLogic = new MetadataLogic($service);
        $metadataLogic->set_app_id($appId);
        $logic->set_metadata_logic($metadataLogic);
        $logic->perform();
    } catch (Exception $exception) {
        error_log(($exception instanceof JsonException ? 'Metadata import and plugin registration failed, invalid installConfig.json: ' : '') . $exception->getMessage());
        return;
    }
}
