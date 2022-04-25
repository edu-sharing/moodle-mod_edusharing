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
 * Internal library of functions for module edusharing
 *
 * All the edusharing specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/edusharing/lib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Get the parameter for authentication
 * @return string
 */
function edusharing_get_auth_key() {

    global $USER, $SESSION;

    // Set by external sso script.
    if (isset($SESSION -> edusharing_sso) && !empty($SESSION -> edusharing_sso)) {
        $eduauthparamnameuserid = get_config('edusharing', 'EDU_AUTH_PARAM_NAME_USERID');
        return $SESSION -> edusharing_sso[$eduauthparamnameuserid];
    }

    $guestoption = get_config('edusharing', 'edu_guest_option');
    if (!empty($guestoption)) {
        $guestid = get_config('edusharing', 'edu_guest_guest_id');
        if (empty($guestid)) {
            $guestid = 'esguest';
        }
        return $guestid;
    }

    $eduauthkey = get_config('edusharing', 'EDU_AUTH_KEY');

    if($eduauthkey == 'id')
        return $USER->id;
    if($eduauthkey == 'idnumber')
        return $USER->idnumber;
    if($eduauthkey == 'email')
        return $USER->email;
    if(isset($USER->profile[$eduauthkey]))
        return $USER->profile[$eduauthkey];
    return $USER->username;
}


/**
 * Return data for authByTrustedApp
 *
 * @return array
 */
function edusharing_get_auth_data() {

    global $USER, $CFG, $SESSION;

    // Set by external sso script.
    if (isset($SESSION -> edusharing_sso) && !empty($SESSION -> edusharing_sso)) {
        $authparams = array();
        foreach ($SESSION -> edusharing_sso as $key => $value) {
            $authparams[] = array('key'  => $key, 'value'  => $value);
        }
    } else {
        // Keep defaults in sync with settings.php.
        $eduauthparamnameuserid = get_config('edusharing', 'EDU_AUTH_PARAM_NAME_USERID');
        if (empty($eduauthparamnameuserid)) {
            $eduauthparamnameuserid = '';
        }

        $eduauthparamnamelastname = get_config('edusharing', 'EDU_AUTH_PARAM_NAME_LASTNAME');
        if (empty($eduauthparamnamelastname)) {
            $eduauthparamnamelastname = '';
        }

        $eduauthparamnamefirstname = get_config('edusharing', 'EDU_AUTH_PARAM_NAME_FIRSTNAME');
        if (empty($eduauthparamnamefirstname)) {
            $eduauthparamnamefirstname = '';
        }

        $eduauthparamnameemail = get_config('edusharing', 'EDU_AUTH_PARAM_NAME_EMAIL');
        if (empty($eduauthparamnameemail)) {
            $eduauthparamnameemail = '';
        }

        $eduauthaffiliation = get_config('edusharing', 'EDU_AUTH_AFFILIATION');

        $eduauthaffiliationname = get_config('edusharing', 'EDU_AUTH_AFFILIATION_NAME');

        $guestoption = get_config('edusharing', 'edu_guest_option');
        if (!empty($guestoption)) {
            $guestid = get_config('edusharing', 'edu_guest_guest_id');
            if (empty($guestid)) {
                $guestid = 'esguest';
            }

            $authparams = array(
                array('key'  => $eduauthparamnameuserid, 'value'  => $guestid),
                array('key'  => $eduauthparamnamelastname, 'value'  => ''),
                array('key'  => $eduauthparamnamefirstname, 'value'  => ''),
                array('key'  => $eduauthparamnameemail, 'value'  => ''),
                array('key'  => 'affiliation', 'value'  => $eduauthaffiliation),
                array('key'  => 'affiliationname', 'value' => $eduauthaffiliationname)
            );
        } else {
            $authparams = array(
                array('key'  => $eduauthparamnameuserid, 'value'  => edusharing_get_auth_key()),
                array('key'  => $eduauthparamnamelastname, 'value'  => $USER->lastname),
                array('key'  => $eduauthparamnamefirstname, 'value'  => $USER->firstname),
                array('key'  => $eduauthparamnameemail, 'value'  => $USER->email),
                array('key'  => 'affiliation', 'value'  => $eduauthaffiliation),
                array('key'  => 'affiliationname', 'value' => $eduauthaffiliationname)
            );
        }
    }

    if (get_config('edusharing', 'EDU_AUTH_CONVEYGLOBALGROUPS') == 'yes' ||
            get_config('edusharing', 'EDU_AUTH_CONVEYGLOBALGROUPS') == '1') {
        $authparams[] = array('key'  => 'globalgroups', 'value'  => edusharing_get_user_cohorts());
    }
    return $authparams;
}

/**
 * Get cohorts the user belongs to
 *
 * @return array
 */
function edusharing_get_user_cohorts() {
    global $DB, $USER;
    $ret = array();
    $cohortmemberships = $DB->get_records('cohort_members', array('userid'  => $USER->id));
    if ($cohortmemberships) {
        foreach ($cohortmemberships as $cohortmembership) {
            $cohort = $DB->get_record('cohort', array('id'  => $cohortmembership->cohortid));
            if($cohort->contextid == 1)
                $ret[] = array(
                        'id'  => $cohortmembership->cohortid,
                        'contextid'  => $cohort->contextid,
                        'name'  => $cohort->name,
                        'idnumber'  => $cohort->idnumber
                );
        }
    }
    return json_encode($ret);
}

/**
 * Generate redirection-url
 *
 * @param stdClass $edusharing
 * @param string $displaymode
 *
 * @return string
 */

function edusharing_get_redirect_url(
    stdClass $edusharing,
    $displaymode = EDUSHARING_DISPLAY_MODE_DISPLAY) {
    global $USER;

    $url = get_config('edusharing', 'application_cc_gui_url') . '/renderingproxy';

    $url .= '?app_id='.urlencode(get_config('edusharing', 'application_appid'));

    $url .= '&session='.urlencode(session_id());

    $repid = edusharing_get_repository_id_from_url($edusharing->object_url);
    $url .= '&rep_id='.urlencode($repid);

    $url .= '&obj_id='.urlencode(edusharing_get_object_id_from_url($edusharing->object_url));

    $url .= '&resource_id='.urlencode($edusharing->id);
    $url .= '&course_id='.urlencode($edusharing->course);

    $context = context_course::instance($edusharing->course);
    $roles = get_user_roles($context, $USER->id);
    foreach ($roles as $role) {
        $url .= '&role=' . urlencode($role -> shortname);
    }

    $url .= '&display='.urlencode($displaymode);
    
    $url .= '&version=' . urlencode($edusharing->object_version);
    $url .= '&locale=' . urlencode(current_language()); //repository
    $url .= '&language=' . urlencode(current_language()); //rendering service

    $url .= '&u='. rawurlencode(base64_encode(edusharing_encrypt_with_repo_public(edusharing_get_auth_key())));

    return $url;
}

/**
 * Generate ssl signature
 *
 * @param string $data
 * @return string
 */
function edusharing_get_signature($data) {
    $privkey = get_config('edusharing', 'application_private_key');
    $pkeyid = openssl_get_privatekey($privkey);
    openssl_sign($data, $signature, $pkeyid);
    $signature = base64_encode($signature);
    openssl_free_key($pkeyid);
    return $signature;
}

/**
 * Return openssl encrypted data
 * Uses repositorys openssl public key
 *
 * @param string $data
 * @return string
 */
function edusharing_encrypt_with_repo_public($data) {
    $crypted = '';
    $key = openssl_get_publickey(get_config('edusharing', 'repository_public_key'));
    openssl_public_encrypt($data ,$crypted, $key);
    if($crypted === false) {
        trigger_error(get_string('error_encrypt_with_repo_public', 'edusharing'), E_USER_WARNING);
        return false;
    }
    return $crypted;
}

/**
 * Fill in the metadata from the repository
 * Returns true on success
 *
 * @param string $metadataurl
 * @return bool
 */
function edusharing_import_metadata($metadataurl, $appId = null, $hostAliases = null, $wlo_guestuser = null){
    global $CFG;
    try {

        $xml = new DOMDocument();

        libxml_use_internal_errors(true);

        $curl = new curl();
        $curl->setopt( array(
            'CURLOPT_SSL_VERIFYPEER' => false,
            'CURLOPT_SSL_VERIFYHOST' => false,
            'CURLOPT_FOLLOWLOCATION' => 1,
            'CURLOPT_HEADER' => 0,
            'CURLOPT_RETURNTRANSFER' => 1,
            'CURLOPT_USERAGENT' => $_SERVER['HTTP_USER_AGENT'],
        ));

        $properties = $curl->get($metadataurl);

        if ($curl->error) {
            debugging('cURL Error: '.$curl->error);
            echo ('<p style="background: #FF8170">cURL Error: '.$curl->error ) . '<br></p>';
            echo get_form($metadataurl);
            return false;
        }

        if ($xml->loadXML($properties) == false) {
            echo ('<p style="background: #FF8170">could not load ' . $metadataurl .
                    ' please check url') . "<br></p>";
            echo get_form($metadataurl);
            return false;
        }

        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;
        $entrys = $xml->getElementsByTagName('entry');
        foreach ($entrys as $entry) {
            set_config('repository_'.$entry->getAttribute('key'), $entry->nodeValue, 'edusharing');
        }

        require_once(dirname(__FILE__) . '/AppPropertyHelper.php');


        $host = $_SERVER['SERVER_ADDR'];
        if(empty($host)){
            $host = gethostbyname($_SERVER['SERVER_NAME']);
        }

        // only update appId on new install
        $currentAppId = get_config('edusharing', 'application_appid');
        if( empty($appId) && empty($currentAppId) ){
            $appId = uniqid('moodle_');
        }else if (!empty($currentAppId)){
            $appId = $currentAppId;
        }

        set_config('application_host', $host, 'edusharing');
        set_config('application_appid', $appId, 'edusharing');
        set_config('application_type', 'LMS', 'edusharing');
        set_config('application_homerepid', get_config('edusharing', 'repository_appid'), 'edusharing');
        set_config('application_cc_gui_url', get_config('edusharing', 'repository_clientprotocol') . '://' .
            get_config('edusharing', 'repository_domain') . ':' .
            get_config('edusharing', 'repository_clientport') . '/edu-sharing/', 'edusharing');

        if (!empty($hostAliases)){
            set_config('application_host_aliases', $hostAliases, 'edusharing');
        }

        if (!empty($wlo_guestuser)){
            set_config('wlo_guest_option', '1', 'edusharing');
            set_config('edu_guest_guest_id', $wlo_guestuser, 'edusharing');
        }

        if (empty(get_config('edusharing', 'application_private_key')) || empty(get_config('edusharing', 'application_public_key')) ){
            $modedusharingapppropertyhelper = new mod_edusharing_app_property_helper();
            $sslkeypair = $modedusharingapppropertyhelper->edusharing_get_ssl_keypair();
            set_config('application_private_key', $sslkeypair['privateKey'], 'edusharing');
            set_config('application_public_key', $sslkeypair['publicKey'], 'edusharing');
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

        if (empty($sslkeypair['privateKey']) && empty(get_config('edusharing', 'application_private_key')) ) {
            echo '<h3 class="edu_error">Generating of SSL keys failed. Please check your configuration.</h3>';
        } else {
            echo '<h3 class="edu_success">Import successful.</h3>';
        }
        return true;
    } catch (Exception $e) {
        echo $e->getMessage();
        return false;
    }
}

function createXmlMetadata(){
    global $CFG;
    $xml = new SimpleXMLElement(
        '<?xml version="1.0" encoding="utf-8" ?><!DOCTYPE properties SYSTEM "http://java.sun.com/dtd/properties.dtd"><properties></properties>');

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

    if(get_config('edusharing', 'wlo_guest_option')){
        $entry = $xml->addChild('entry', get_config('edusharing', 'edu_guest_guest_id'));
        $entry->addAttribute('key', 'auth_by_app_user_whitelist');
    }

    return html_entity_decode($xml->asXML());
}

function createApiBody($data, $delimiter){
    $body = '';
    $body .= '--' . $delimiter. "\r\n";
    $body .= 'Content-Disposition: form-data; name="' . 'xml' . '"';
    $body .= '; filename="metadata.xml"' . "\r\n";
    $body .= 'Content-Type: text/xml' ."\r\n\r\n";
    $body .= $data."\r\n";
    $body .= "--" . $delimiter . "--\r\n";

    return $body;
}

function registerPlugin($repoUrl, $login, $pwd, $data){
    $url = $repoUrl.'rest/authentication/v1/validateSession';

    $auth = $login.':'.$pwd;
    $delimiter = '-------------'.uniqid();
    $post = createApiBody( $data, $delimiter);

    $curlSession = new curl();
    $curlSession->header = array(
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic '.base64_encode( $auth )
    );
    $curlSession->setopt( array(
        'CURLOPT_RETURNTRANSFER' => 1,
    ));

    $result = $curlSession->get($url);
    if(json_decode($result)->isAdmin == false){
        throw new \Exception('Given user / password was not accepted as admin: ' . $result);
    }

    $urlXML = $repoUrl.'rest/admin/v1/applications/xml';
    $curlXML = new curl();
    $curlXML->header = array(
        'Content-Type: multipart/form-data; boundary=' . $delimiter,
        'Content-Length: ' . strlen($post),
        'Accept: application/json',
        'Authorization: Basic '.base64_encode( $auth )
    );
    $curlXML->setopt( array(
        'CURLOPT_RETURNTRANSFER' => 1,
    ));

    $result = $curlXML->put($urlXML, $post);

    if ($curlXML->error) {
        debugging('cURL Error: '.$curlXML->error);
        $result = 'cURL Error: '.$curlXML->error;
    }

    return $result;
}

