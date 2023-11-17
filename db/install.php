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
        $logic->parseConfigData();
        $appId = $logic->discernAppId();
        $data  = $logic->getConfigData();
        $utils = new UtilityFunctions();
        $utils->setConfigEntry('application_appid', $appId);
        $utils->setConfigEntry('send_additional_auth', '1');
        if (empty($data['repoUrl']) || empty($data['repoAdmin']) || empty($data['repoAdminPassword'])) {
            return;
        }
        $baseHelper = new EduSharingHelperBase($data['repoUrl'], '', $appId);
        $baseHelper->registerCurlHandler(new MoodleCurlHandler());
        $authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
        $service    = new EduSharingService($authHelper, $nodeHelper);
        $logic->setRegistrationLogic(new PluginRegistration($service));
        $metadataLogic = new MetadataLogic($service);
        $metadataLogic->setAppId($appId);
        $logic->setMetadataLogic($metadataLogic);
        $logic->perform();
    } catch (Exception $exception) {
        error_log(($exception instanceof JsonException ? 'Metadata import and plugin registration failed, invalid installConfig.json: ' : '') . $exception->getMessage());
        return;
    }
}
