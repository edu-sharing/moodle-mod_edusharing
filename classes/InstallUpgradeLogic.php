<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

declare(strict_types = 1);

namespace mod_edusharing;

use Exception;
use JsonException;

/**
 * Class InstallUpgradeLogic
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class InstallUpgradeLogic {
    /**
     * @var PluginRegistration|null
     */
    private ?PluginRegistration $registrationlogic = null;
    /**
     * @var MetadataLogic|null
     */
    private ?MetadataLogic      $metadatalogic     = null;
    /**
     * @var string
     */
    private string $configpath;
    /**
     * @var array|null
     */
    private ?array $configdata = null;

    /**
     * InstallUpgradeLogic constructor
     *
     * @param string $configpath
     */
    public function __construct(string $configpath = __DIR__ . '/../db/installConfig.json') {
        $this->configpath = $configpath;
    }

    /**
     * Function parse_config_data
     *
     * @throws JsonException
     * @throws Exception
     */
    public function parse_config_data(): void {
        if (! empty(getenv('EDUSHARING_RENDER_DOCKER_DEPLOYMENT'))) {
            $port = empty(getenv('EDUSHARING_REPOSITORY_PORT')) ? '' : (':' . getenv('EDUSHARING_REPOSITORY_PORT'));
            $this->configdata = [
                'repoUrl' => getenv('EDUSHARING_REPOSITORY_PROT') . '://' . getenv('EDUSHARING_REPOSITORY_HOST') . $port . '/edu-sharing',
                'repoAdmin' => getenv('EDUSHARING_REPOSITORY_USERNAME'),
                'repoAdminPassword' => getenv('EDUSHARING_REPOSITORY_PASSWORD'),
                'autoAppIdFromUrl' => false
            ];
            error_log(json_encode($this->configdata));
            return;
        }
        if (! file_exists($this->configpath)) {
            throw new Exception('Metadata import and plugin registration failed: Missing installConfig.json');
        }
        $jsonstring       = file_get_contents($this->configpath);
        $this->configdata = json_decode($jsonstring, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Function perform
     *
     * @param bool $isinstall
     * @return void
     */
    public function perform(bool $isinstall = true): void {
        error_log("RUNNING PERFORM");
        global $CFG;
        if (in_array(null, [$this->metadatalogic, $this->registrationlogic, $this->configdata], true)
            || empty($this->configdata['repoAdmin']) || empty($this->configdata['repoAdminPassword'])
        ) {
            return;
        }
        $metadataurl = rtrim($this->configdata['repoUrl'], '/') . '/metadata?format=lms&external=true';
        if ($isinstall && $this->configdata['autoAppIdFromUrl']) {
            $this->metadatalogic->set_app_id(basename($CFG->wwwroot));
        }
        if (! empty($this->configdata['wloGuestUser_optional'])) {
            $this->metadatalogic->set_wlo_guest_user($this->configdata['wloGuestUser_optional']);
        }
        if (! empty($this->configdata['hostAliases_optional'])) {
            $this->metadatalogic->set_host_aliases($this->configdata['hostAliases_optional']);
        }
        try {
            $this->metadatalogic->import_metadata($metadataurl, $this->configdata['host'] ?? null);
            $repourl            = get_config('edusharing', 'application_cc_gui_url');
            $data               = $this->metadatalogic->create_xml_metadata();
            $registrationresult = $this->registrationlogic->register_plugin(
                $repourl,
                $this->configdata['repoAdmin'],
                $this->configdata['repoAdminPassword'],
                $data
            );
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return;
        }
        if (! isset($registrationresult['appid'])) {
            debugging('Automatic plugin registration could not be performed.');
        }
    }

    /**
     * Function get_config_data
     *
     * @return array
     */
    public function get_config_data(): array {
        return $this->configdata ?? [];
    }

    /**
     * Function set_registration_logic
     *
     * @param PluginRegistration $pluginregistration
     * @return void
     */
    public function set_registration_logic(PluginRegistration $pluginregistration): void {
        $this->registrationlogic = $pluginregistration;
    }

    /**
     * Function set_metadata_logic
     *
     * @param MetadataLogic $metadatalogic
     * @return void
     */
    public function set_metadata_logic(MetadataLogic $metadatalogic): void {
        $this->metadatalogic = $metadatalogic;
    }

    /**
     * Function discern_app_id
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
    public function discern_app_id(): string {
        $utils = new UtilityFunctions();
        try {
            $appid = empty($utils->get_config_entry('application_appid')) ? false : $utils->get_config_entry('application_appid');
        } catch (Exception $exception) {
            unset($exception);
            $appid = false;
        }
        if ($appid === false) {
            $appid = $this->get_config_data()['moodleAppId_optional'];
            if (empty($appid)) {
                $appid = uniqid('moodle_');
            }
        } else {
            $appid = (string)$appid;
        }
        return $appid;
    }
}
