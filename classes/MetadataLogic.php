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

declare(strict_types=1);

namespace mod_edusharing;

use dml_exception;
use DOMDocument;
use EduSharingApiClient\EduSharingHelper;
use Exception;
use SimpleXMLElement;

/**
 * Class MetadataLogic
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MetadataLogic {
    /**
     * @var bool
     */
    public bool $reloadform = false;
    /**
     * @var string|null
     */
    private ?string $hostaliases = null;
    /**
     * @var string|null
     */
    private ?string $wloguestuser = null;
    /**
     * @var string|null
     */
    private ?string $appid = null;
    /**
     * @var EduSharingService
     */
    private EduSharingService $service;
    /**
     * @var UtilityFunctions|null
     */
    private ?UtilityFunctions $utils;

    /**
     * MetadataLogic constructor
     *
     * @param EduSharingService $service
     * @param UtilityFunctions|null $utils
     */
    public function __construct(EduSharingService $service, ?UtilityFunctions $utils = null) {
        $this->service = $service;
        $this->utils   = $utils;
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
        $this->init();
    }

    /**
     * Function init
     *
     * @return void
     */
    private function init(): void {
        $this->utils === null && $this->utils = new UtilityFunctions();
    }

    /**
     * Function import_metadata
     *
     * @param string $metadataurl
     * @param string|null $host
     * @throws EduSharingUserException
     * @throws dml_exception
     */
    public function import_metadata(string $metadataurl, ?string $host = null): void {
        error_log("IMPORT METADATA");
        global $CFG;
        $xml = new DOMDocument();
        libxml_use_internal_errors(true);
        $result = $this->service->import_metadata($metadataurl);
        error_log("result: " . json_encode($result));
        if ($result->error !== 0) {
            $message = $result->info['message'] ?? 'unknown';
            debugging('cURL Error: ' . $message);
            $this->reloadform = true;
            throw new EduSharingUserException($message, 0, null,
                '<p style="background: #FF8170">cURL Error: ' . $message . '<br></p>');
        }
        if (!$xml->loadXML($result->content)) {
            $this->reloadform = true;
            throw new EduSharingUserException('xml error', 0, null,
                '<p style="background: #FF8170">could not load ' . $metadataurl . ' please check url <br></p>');
        }
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput       = true;
        $entries                 = $xml->getElementsByTagName('entry');
        if ($this->appid === null) {
            $this->appid = !empty($this->utils->get_config_entry('application_appid'))
                ? $this->utils->get_config_entry('application_appid') : uniqid('moodle_');
        }
        $repoid     = $this->utils->get_config_entry('repository_appid');
        $privatekey = $this->utils->get_config_entry('application_private_key');
        $publickey  = $this->utils->get_config_entry('application_public_key');
        foreach ($entries as $entry) {
            $this->utils->set_config_entry('repository_' . $entry->getAttribute('key'), $entry->nodeValue);
        }
        if (empty ($host)) {
            if (!empty($_SERVER['SERVER_ADDR'])) {
                $host = $_SERVER['SERVER_ADDR'];
            } else if (!empty($_SERVER['SERVER_NAME'])) {
                $host = gethostbyname($_SERVER['SERVER_NAME']);
            } else {
                throw new Exception('Host could not be discerned. Cancelling ES-registration process.');
            }
        }
        $clientprotocol = $this->utils->get_config_entry('repository_clientprotocol');
        $repodomain     = $this->utils->get_config_entry('repository_domain');
        $clientport     = $this->utils->get_config_entry('repository_clientport');
        $this->utils->set_config_entry('application_host', $host);
        $this->utils->set_config_entry('application_appid', $this->appid);
        $this->utils->set_config_entry('application_type', 'LMS');
        $this->utils->set_config_entry('application_homerepid', $repoid);
        $this->utils->set_config_entry(
            'application_cc_gui_url', $clientprotocol . '://' . $repodomain . ':' . $clientport . '/edu-sharing/'
        );
        if ($this->hostaliases !== null) {
            $this->utils->set_config_entry('application_host_aliases', $this->hostaliases);
        }
        if ($this->wloguestuser !== null) {
            $this->utils->set_config_entry('wlo_guest_option', '1');
            $this->utils->set_config_entry('edu_guest_guest_id', $this->wloguestuser);
        }
        if (empty($privatekey) || empty($publickey)) {
            $keypair = EduSharingHelper::generateKeyPair();
            $this->utils->set_config_entry('application_private_key', $keypair['privateKey']);
            $this->utils->set_config_entry('application_public_key', $keypair['publicKey']);
        }
        if (empty($this->utils->get_config_entry('application_private_key'))) {
            throw new EduSharingUserException('ssl keypair generation error', 0, null,
                '<h3 class="edu_error">Generating of SSL keys failed. Please check your configuration.</h3>');
        }
        $this->utils->set_config_entry('application_blowfishkey', 'thetestkey');
        $this->utils->set_config_entry('application_blowfishiv', 'initvect');
        $this->utils->set_config_entry('EDU_AUTH_KEY', 'username');
        $this->utils->set_config_entry('EDU_AUTH_PARAM_NAME_USERID', 'userid');
        $this->utils->set_config_entry('EDU_AUTH_PARAM_NAME_LASTNAME', 'lastname');
        $this->utils->set_config_entry('EDU_AUTH_PARAM_NAME_FIRSTNAME', 'firstname');
        $this->utils->set_config_entry('EDU_AUTH_PARAM_NAME_EMAIL', 'email');
        $this->utils->set_config_entry('EDU_AUTH_AFFILIATION', $CFG->siteidentifier);
        $this->utils->set_config_entry('EDU_AUTH_AFFILIATION_NAME', $CFG->siteidentifier);
    }

    /**
     * Function create_xml_metadata
     *
     * @return string
     */
    public function create_xml_metadata(): string {
        global $CFG;
        // phpcs:disable -- This is just a long string.
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="utf-8" ?><!DOCTYPE properties SYSTEM "http://java.sun.com/dtd/properties.dtd"><properties></properties>');
        // phpcs:enable
        try {
            $entry = $xml->addChild('entry', $this->utils->get_config_entry('application_appid'));
            $entry->addAttribute('key', 'appid');
            $entry = $xml->addChild('entry', $this->utils->get_config_entry('application_type'));
            $entry->addAttribute('key', 'type');
            $entry = $xml->addChild('entry', 'moodle');
            $entry->addAttribute('key', 'subtype');
            $entry = $xml->addChild('entry', parse_url($CFG->wwwroot, PHP_URL_HOST));
            $entry->addAttribute('key', 'domain');
            $entry = $xml->addChild('entry', $this->utils->get_config_entry('application_host') === false
                ? '' : $this->utils->get_config_entry('application_host'));
            $entry->addAttribute('key', 'host');
            $entry = $xml->addChild('entry', $this->utils->get_config_entry('application_host_aliases'));
            $entry->addAttribute('key', 'host_aliases');
            $entry = $xml->addChild('entry', 'true');
            $entry->addAttribute('key', 'trustedclient');
            $entry = $xml->addChild('entry', 'moodle:course/update');
            $entry->addAttribute('key', 'hasTeachingPermission');
            $entry = $xml->addChild('entry', $this->utils->get_config_entry('application_public_key'));
            $entry->addAttribute('key', 'public_key');
            $entry = $xml->addChild('entry', $this->utils->get_config_entry('EDU_AUTH_AFFILIATION_NAME'));
            $entry->addAttribute('key', 'appcaption');
            if ($this->utils->get_config_entry('wlo_guest_option')) {
                $entry = $xml->addChild('entry', $this->utils->get_config_entry('edu_guest_guest_id'));
                $entry->addAttribute('key', 'auth_by_app_user_whitelist');
            }
        } catch (dml_exception $exception) {
            unset($exception);

            return '';
        }

        return html_entity_decode($xml->asXML(), ENT_COMPAT);
    }

    /**
     * Function set_host_aliases
     *
     * @param string $hostaliases
     * @return void
     */
    public function set_host_aliases(string $hostaliases): void {
        $this->hostaliases = $hostaliases;
    }

    /**
     * Function set_wlo_guest_user
     *
     * @param string $wloguestuser
     * @return void
     */
    public function set_wlo_guest_user(string $wloguestuser): void {
        $this->wloguestuser = $wloguestuser;
    }


    /**
     * Function set_app_id
     *
     * @param string $appid
     * @return void
     */
    public function set_app_id(string $appid): void {
        $this->appid = $appid;
    }
}
