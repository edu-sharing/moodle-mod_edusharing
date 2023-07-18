<?php

namespace mod_edusharing;

use Exception;
use JsonException;

class InstallUpgradeLogic
{
    public static function perform(bool $isInstall = true): void {
        global $CFG;
        try {
            $configData = json_decode($CFG->dirroot . '/mod/edusharing/db/installConfig.json', true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            unset($jsonException);
            error_log('Metadata import and plugin registration failed: Invalid installConfig.json');
            return;
        }
        if (empty($configData['repoAdmin']) || empty($configData['repoAdminPassword'])) {
            return;
        }
        $logic       = new MetadataLogic();
        $metadataUrl = $configData['repoUrl'] . '/metadata?format=lms&external=true';
        if ($isInstall) {
            $logic->setAppId($configData['autoAppIdFromUrl'] ? basename($CFG->wwwroot) : $configData['moodleAppId_optional']);
        }
        ! empty($configData['wloGuestUser_optional']) && $logic->setWloGuestUser($configData['wloGuestUser_optional']);
        ! empty($configData['hostAliases_optional']) && $logic->setHostAliases($configData['hostAliases_optional']);
        try {
            $logic->importMetadata($metadataUrl);
            $repoUrl            = get_config('edusharing', 'application_cc_gui_url');
            $data               = $logic->createXmlMetadata();
            $registrationLogic  = new PluginRegistration();
            $registrationResult = $registrationLogic->registerPlugin($repoUrl, $configData['repoAdmin'], $configData['repoAdminPassword'], $data);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return;
        }
        if (isset($registrationResult['appid'])) {
            error_log('Automatic plugin registration could not be performed.');
        }
    }
}