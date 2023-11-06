<?php declare(strict_types = 1);

namespace mod_edusharing;

use Exception;
use JsonException;

/**
 * Class InstallUpgradeLogic
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
class InstallUpgradeLogic
{
    private ?PluginRegistration $registrationLogic = null;
    private ?MetadataLogic $metadataLogic = null;

    private string $configPath;
    private ?array $configData = null;

    /**
     * InstallUpgradeLogic constructor
     *
     * @param string $configPath
     */
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

    /**
     * Function perform
     *
     * @param bool $isInstall
     * @return void
     */
    public function perform(bool $isInstall = true): void {
        global $CFG;
        if (in_array(null, [$this->metadataLogic, $this->registrationLogic, $this->configData], true) || empty($this->configData['repoAdmin']) || empty($this->configData['repoAdminPassword'])) {
            return;
        }
        $metadataUrl = $this->configData['repoUrl'] . '/metadata?format=lms&external=true';
        if ($isInstall && $this->configData['autoAppIdFromUrl']) {
            $this->metadataLogic->setAppId(basename($CFG->wwwroot));
        }
        ! empty($this->configData['wloGuestUser_optional']) && $this->metadataLogic->setWloGuestUser($this->configData['wloGuestUser_optional']);
        ! empty($this->configData['hostAliases_optional']) && $this->metadataLogic->setHostAliases($this->configData['hostAliases_optional']);
        try {
            $this->metadataLogic->importMetadata($metadataUrl, $this->configData['host'] ?? null);
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

    /**
     * Function getConfigData
     *
     * @return array
     */
    public function getConfigData(): array {
        return $this->configData ?? [];
    }

    /**
     * Function setRegistrationLogic
     *
     * @param PluginRegistration $pluginRegistration
     * @return void
     */
    public function setRegistrationLogic(PluginRegistration $pluginRegistration): void {
        $this->registrationLogic = $pluginRegistration;
    }

    /**
     * Function setMetadataLogic
     *
     * @param MetadataLogic $metadataLogic
     * @return void
     */
    public function setMetadataLogic(MetadataLogic $metadataLogic): void {
        $this->metadataLogic = $metadataLogic;
    }

    /**
     * Function discernAppId
     *
     * During install and upgrade an appId has to be set.
     * This function discerns and returns it.
     * Priority (highest to lowest):
     * - configured preexisting app id (from get_config)
     * - app id provided in installConfig.json
     * - auto generated new app id
     *
     * @return string
     */
    public function discernAppId(): string {
        $utils = new UtilityFunctions();
        try {
            $appId = empty($utils->getConfigEntry('application_appid')) ? false : $utils->getConfigEntry('application_appid');
        } catch (Exception $exception) {
            unset($exception);
            $appId = false;
        }
        if ($appId === false) {
            $appId = $this->getConfigData()['moodleAppId_optional'];
            if (empty($appId)) {
                $appId = uniqid('moodle_');
            }
        } else {
            $appId = (string)$appId;
        }
        return $appId;
    }
}
