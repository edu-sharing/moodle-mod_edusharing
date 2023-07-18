<?php declare(strict_types = 1);

namespace mod_edusharing;

use dml_exception;
use DOMDocument;
use EduSharingApiClient\EduSharingHelper;
use Exception;
use mod_edusharing\apiService\MoodleCurlHandler;
use SimpleXMLElement;

class MetadataLogic
{
    public bool $reloadForm = false;
    private ?string $hostAliases = null;
    private ?string $wloGuestUser = null;
    private ?string $appId = null;
    public function __construct() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
    }

    /**
     * @throws EduSharingUserException
     * @throws Exception
     */
    public function importMetadata(string $metaDataUrl): void {
        global $CFG;
        $xml         = new DOMDocument();
        $curlHandler = new MoodleCurlHandler();
        libxml_use_internal_errors(true);
        $curlOptions = [
            'CURLOPT_SSL_VERIFYPEER' => false,
            'CURLOPT_SSL_VERIFYHOST' => false,
            'CURLOPT_FOLLOWLOCATION' => 1,
            'CURLOPT_HEADER'         => 0,
            'CURLOPT_RETURNTRANSFER' => 1,
            'CURLOPT_USERAGENT'      => $_SERVER['HTTP_USER_AGENT']
        ];
        $result = $curlHandler->handleCurlRequest($metaDataUrl, $curlOptions);
        if ($result->error !== 0) {
            $message = $result->info['message'] ?? 'unknown';
            debugging('cURL Error: '. $message);
            $this->reloadForm = true;
            throw new EduSharingUserException($message, 0, null, '<p style="background: #FF8170">cURL Error: '. $message . '<br></p>');
        }
        if (! $xml->loadXML($result->content)) {
            $this->reloadForm = true;
            throw new EduSharingUserException('xml error', 0, null, '<p style="background: #FF8170">could not load ' . $metaDataUrl . ' please check url <br></p>');
        }
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput       = true;
        $entries                 = $xml->getElementsByTagName('entry');
        if ($this->appId === null) {
            $this->appId = empty(get_config('edusharing', 'application_appid')) ? get_config('edusharing', 'application_appid') : uniqid('moodle_');
        }
        $repoId                  = get_config('edusharing', 'repository_appid');
        $clientProtocol          = get_config('edusharing', 'repository_clientprotocol');
        $repoDomain              = get_config('edusharing', 'repository_domain');
        $clientPort              = get_config('edusharing', 'repository_clientport');
        $privateKey              = get_config('edusharing', 'application_private_key');
        $publicKey               = get_config('edusharing', 'application_public_key');
        foreach ($entries as $entry) {
            set_config('repository_' . $entry->getAttribute('key'), $entry->nodeValue, 'edusharing');
        }
        $host = empty($_SERVER['SERVER_ADDR']) ? gethostbyname($_SERVER['SERVER_NAME']) : $_SERVER['SERVER_ADDR'];
        set_config('application_host', $host, 'edusharing');
        set_config('application_appid', $this->appId, 'edusharing');
        set_config('application_type', 'LMS', 'edusharing');
        set_config('application_homerepid', $repoId, 'edusharing');
        set_config('application_cc_gui_url', $clientProtocol . '://' . $repoDomain . ':' . $clientPort . '/edu-sharing/', 'edusharing');
        if ($this->hostAliases !== null) {
            set_config('application_host_aliases', $this->hostAliases, 'edusharing');
        }
        if ($this->wloGuestUser !== null) {
            set_config('wlo_guest_option', '1', 'edusharing');
            set_config('edu_guest_guest_id', $this->wloGuestUser, 'edusharing');
        }
        if (empty($privateKey) || empty($publicKey) ){
            $keyPair = EduSharingHelper::generateKeyPair();
            set_config('application_private_key', $keyPair['privateKey'], 'edusharing');
            set_config('application_public_key', $keyPair['publicKey'], 'edusharing');
        }
        if (empty(get_config('edusharing', 'application_private_key'))) {
            throw new EduSharingUserException('ssl keypair generation error', 0, null, '<h3 class="edu_error">Generating of SSL keys failed. Please check your configuration.</h3>');
        }
        set_config('application_blowfishkey', 'thetestkey', 'edusharing');
        set_config('application_blowfishiv', 'initvect', 'edusharing');
        set_config('EDU_AUTH_KEY', 'username', 'edusharing');
        set_config('EDU_AUTH_PARAM_NAME_USERID', 'userid', 'edusharing');
        set_config('EDU_AUTH_PARAM_NAME_LASTNAME', 'lastname', 'edusharing');
        set_config('EDU_AUTH_PARAM_NAME_FIRSTNAME', 'firstname', 'edusharing');
        set_config('EDU_AUTH_PARAM_NAME_EMAIL', 'email', 'edusharing');
        set_config('EDU_AUTH_AFFILIATION', $CFG->siteidentifier, 'edusharing');
        set_config('EDU_AUTH_AFFILIATION_NAME', $CFG->siteidentifier, 'edusharing');
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
            $entry = $xml->addChild('entry', get_config('edusharing', 'application_appid'));
            $entry->addAttribute('key', 'appid');
            $entry = $xml->addChild('entry', get_config('edusharing', 'application_type'));
            $entry->addAttribute('key', 'type');
            $entry = $xml->addChild('entry', 'moodle');
            $entry->addAttribute('key', 'subtype');
            $entry = $xml->addChild('entry', parse_url($CFG->wwwroot, PHP_URL_HOST));
            $entry->addAttribute('key', 'domain');
            $entry = $xml->addChild('entry', get_config('edusharing', 'application_host'));
            $entry->addAttribute('key', 'host');
            $entry = $xml->addChild('entry', get_config('edusharing', 'application_host_aliases'));
            $entry->addAttribute('key', 'host_aliases');
            $entry = $xml->addChild('entry', 'true');
            $entry->addAttribute('key', 'trustedclient');
            $entry = $xml->addChild('entry', 'moodle:course/update');
            $entry->addAttribute('key', 'hasTeachingPermission');
            $entry = $xml->addChild('entry', get_config('edusharing', 'application_public_key'));
            $entry->addAttribute('key', 'public_key');
            $entry = $xml->addChild('entry', get_config('edusharing', 'EDU_AUTH_AFFILIATION_NAME'));
            $entry->addAttribute('key', 'appcaption');

            if (get_config('edusharing', 'wlo_guest_option')) {
                $entry = $xml->addChild('entry', get_config('edusharing', 'edu_guest_guest_id'));
                $entry->addAttribute('key', 'auth_by_app_user_whitelist');
            }
        } catch (dml_exception $exception) {
            unset($exception);

            return '';
        }

        return html_entity_decode($xml->asXML());
    }

    public function setHostAliases(string $hostAliases): void {
        $this->hostAliases = $hostAliases;
    }

    public function setWloGuestUser(string $wloGuestUser): void {
        $this->wloGuestUser = $wloGuestUser;
    }

    public function setAppId(string $appId): void {
        $this->appId = $appId;
    }
}