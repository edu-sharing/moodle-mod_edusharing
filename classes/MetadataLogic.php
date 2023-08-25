<?php declare(strict_types=1);

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
 */
class MetadataLogic
{
    public bool               $reloadForm   = false;
    private ?string           $hostAliases  = null;
    private ?string           $wloGuestUser = null;
    private ?string           $appId        = null;
    private EduSharingService $service;
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
     * Function importMetadata
     *
     * @throws EduSharingUserException
     * @throws Exception
     */
    public function importMetadata(string $metaDataUrl): void {
        global $CFG;
        $xml = new DOMDocument();
        libxml_use_internal_errors(true);
        $result = $this->service->importMetadata($metaDataUrl);
        if ($result->error !== 0) {
            $message = $result->info['message'] ?? 'unknown';
            debugging('cURL Error: ' . $message);
            $this->reloadForm = true;
            throw new EduSharingUserException($message, 0, null, '<p style="background: #FF8170">cURL Error: ' . $message . '<br></p>');
        }
        if (!$xml->loadXML($result->content)) {
            $this->reloadForm = true;
            throw new EduSharingUserException('xml error', 0, null, '<p style="background: #FF8170">could not load ' . $metaDataUrl . ' please check url <br></p>');
        }
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput       = true;
        $entries                 = $xml->getElementsByTagName('entry');
        if ($this->appId === null) {
            $this->appId = !empty($this->utils->getConfigEntry('application_appid')) ? $this->utils->getConfigEntry('application_appid') : uniqid('moodle_');
        }
        $repoId         = $this->utils->getConfigEntry('repository_appid');
        $clientProtocol = $this->utils->getConfigEntry('repository_clientprotocol');
        $repoDomain     = $this->utils->getConfigEntry('repository_domain');
        $clientPort     = $this->utils->getConfigEntry('repository_clientport');
        $privateKey     = $this->utils->getConfigEntry('application_private_key');
        $publicKey      = $this->utils->getConfigEntry('application_public_key');
        foreach ($entries as $entry) {
            $this->utils->setConfigEntry('repository_' . $entry->getAttribute('key'), $entry->nodeValue);
        }
        $host = empty($_SERVER['SERVER_ADDR']) ? gethostbyname($_SERVER['SERVER_NAME']) : $_SERVER['SERVER_ADDR'];
        $this->utils->setConfigEntry('application_host', $host);
        $this->utils->setConfigEntry('application_appid', $this->appId);
        $this->utils->setConfigEntry('application_type', 'LMS');
        $this->utils->setConfigEntry('application_homerepid', $repoId);
        $this->utils->setConfigEntry('application_cc_gui_url', $clientProtocol . '://' . $repoDomain . ':' . $clientPort . '/edu-sharing/');
        if ($this->hostAliases !== null) {
            $this->utils->setConfigEntry('application_host_aliases', $this->hostAliases);
        }
        if ($this->wloGuestUser !== null) {
            $this->utils->setConfigEntry('wlo_guest_option', '1');
            $this->utils->setConfigEntry('edu_guest_guest_id', $this->wloGuestUser);
        }
        if (empty($privateKey) || empty($publicKey)) {
            $keyPair = EduSharingHelper::generateKeyPair();
            $this->utils->setConfigEntry('application_private_key', $keyPair['privateKey']);
            $this->utils->setConfigEntry('application_public_key', $keyPair['publicKey']);
        }
        if (empty($this->utils->getConfigEntry('application_private_key'))) {
            throw new EduSharingUserException('ssl keypair generation error', 0, null, '<h3 class="edu_error">Generating of SSL keys failed. Please check your configuration.</h3>');
        }
        $this->utils->setConfigEntry('application_blowfishkey', 'thetestkey');
        $this->utils->setConfigEntry('application_blowfishiv', 'initvect');
        $this->utils->setConfigEntry('EDU_AUTH_KEY', 'username');
        $this->utils->setConfigEntry('EDU_AUTH_PARAM_NAME_USERID', 'userid');
        $this->utils->setConfigEntry('EDU_AUTH_PARAM_NAME_LASTNAME', 'lastname');
        $this->utils->setConfigEntry('EDU_AUTH_PARAM_NAME_FIRSTNAME', 'firstname');
        $this->utils->setConfigEntry('EDU_AUTH_PARAM_NAME_EMAIL', 'email');
        $this->utils->setConfigEntry('EDU_AUTH_AFFILIATION', $CFG->siteidentifier);
        $this->utils->setConfigEntry('EDU_AUTH_AFFILIATION_NAME', $CFG->siteidentifier);
    }

    /**
     * Function createXmlMetadata
     *
     * @return string
     */
    public function createXmlMetadata(): string {
        global $CFG;
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="utf-8" ?><!DOCTYPE properties SYSTEM "http://java.sun.com/dtd/properties.dtd"><properties></properties>');
        try {
            $entry = $xml->addChild('entry', $this->utils->getConfigEntry('application_appid'));
            $entry->addAttribute('key', 'appid');
            $entry = $xml->addChild('entry', $this->utils->getConfigEntry('application_type'));
            $entry->addAttribute('key', 'type');
            $entry = $xml->addChild('entry', 'moodle');
            $entry->addAttribute('key', 'subtype');
            $entry = $xml->addChild('entry', parse_url($CFG->wwwroot, PHP_URL_HOST));
            $entry->addAttribute('key', 'domain');
            $entry = $xml->addChild('entry', $this->utils->getConfigEntry('application_host'));
            $entry->addAttribute('key', 'host');
            $entry = $xml->addChild('entry', $this->utils->getConfigEntry('application_host_aliases'));
            $entry->addAttribute('key', 'host_aliases');
            $entry = $xml->addChild('entry', 'true');
            $entry->addAttribute('key', 'trustedclient');
            $entry = $xml->addChild('entry', 'moodle:course/update');
            $entry->addAttribute('key', 'hasTeachingPermission');
            $entry = $xml->addChild('entry', $this->utils->getConfigEntry('application_public_key'));
            $entry->addAttribute('key', 'public_key');
            $entry = $xml->addChild('entry', $this->utils->getConfigEntry('EDU_AUTH_AFFILIATION_NAME'));
            $entry->addAttribute('key', 'appcaption');
            if ($this->utils->getConfigEntry('wlo_guest_option')) {
                $entry = $xml->addChild('entry', $this->utils->getConfigEntry('edu_guest_guest_id'));
                $entry->addAttribute('key', 'auth_by_app_user_whitelist');
            }
        } catch (dml_exception $exception) {
            unset($exception);

            return '';
        }

        return html_entity_decode($xml->asXML());
    }

    /**
     * Function setHostAliases
     *
     * @param string $hostAliases
     * @return void
     */
    public function setHostAliases(string $hostAliases): void {
        $this->hostAliases = $hostAliases;
    }

    /**
     * Function setWloGuestUser
     *
     * @param string $wloGuestUser
     * @return void
     */
    public function setWloGuestUser(string $wloGuestUser): void {
        $this->wloGuestUser = $wloGuestUser;
    }


    /**
     * Function setAppId
     *
     * @param string $appId
     * @return void
     */
    public function setAppId(string $appId): void {
        $this->appId = $appId;
    }
}
