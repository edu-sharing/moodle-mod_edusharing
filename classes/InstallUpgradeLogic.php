<?php

namespace mod_edusharing;

use Exception;
use JsonException;

class InstallUpgradeLogic
{
    private ?PluginRegistration $registrationLogic = null;
    private ?MetadataLogic $metadataLogic = null;

    private string $configPath;
    private ?array $configData = null;

    public function __construct(string $configPath = __DIR__ . '/../db/installConfig.json') {
        $this->configPath = $configPath;
    }

    /**
     * Function parseConfigData
     *
     * @throws JsonException
     * @throws Exception
     */
    public function parseConfigData(): void {
        if (! file_exists($this->configPath)) {
            throw new Exception('Metadata import and plugin registration failed: Missing installConfig.json');
        }
        $jsonString       = file_get_contents($this->configPath);
        $this->configData = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
    }

    public function perform(bool $isInstall = true): void {
        global $CFG;
        if (in_array(null, [$this->metadataLogic, $this->registrationLogic, $this->configData], true) || empty($this->configData['repoAdmin']) || empty($this->configData['repoAdminPassword'])) {
            return;
        }
        $metadataUrl = $this->configData['repoUrl'] . '/metadata?format=lms&external=true';
        if ($isInstall) {
            $this->metadataLogic->setAppId($this->configData['autoAppIdFromUrl'] ? basename($CFG->wwwroot) : $this->configData['moodleAppId_optional']);
        }
        ! empty($this->configData['wloGuestUser_optional']) && $this->metadataLogic->setWloGuestUser($this->configData['wloGuestUser_optional']);
        ! empty($this->configData['hostAliases_optional']) && $this->metadataLogic->setHostAliases($this->configData['hostAliases_optional']);
        try {
            $this->metadataLogic->importMetadata($metadataUrl);
            $repoUrl            = get_config('edusharing', 'application_cc_gui_url');
            $data               = $this->metadataLogic->createXmlMetadata();
            $registrationResult = $this->registrationLogic->registerPlugin($repoUrl, $this->configData['repoAdmin'], $this->configData['repoAdminPassword'], $data);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return;
        }
        if (! isset($registrationResult['appid'])) {
            error_log('Automatic plugin registration could not be performed.');
        }
    }

    public function getConfigData(): ?array {
        return $this->configData ?? [];
    }

    public function setRegistrationLogic(PluginRegistration $pluginRegistration): void {
        $this->registrationLogic = $pluginRegistration;
    }

    public function setMetadataLogic(MetadataLogic $metadataLogic): void {
        $this->metadataLogic = $metadataLogic;
    }
}