<?php declare(strict_types=1);

namespace mod_edusharing;

use cached_cm_info;
use coding_exception;
use context_course;
use context_system;
use dml_exception;
use Exception;
use stdClass;

/**
 * Class UtilityFunctions
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
class UtilityFunctions
{
    private ?AppConfig $appConfig;

    /**
     * UtilityFunctions constructor
     *
     * @param AppConfig|null $config
     */
    public function __construct(?AppConfig $config = null) {
        $this->appConfig = $config;
        $this->init();
    }

    /**
     * Function init
     *
     * @return void
     */
    private function init(): void {
        if ($this->appConfig === null) {
            $this->appConfig = new DefaultAppConfig();
        }
    }

    /**
     * Function getObjectIdFromUrl
     *
     * Get the object-id from object-url.
     * E.g. "abc-123-xyz-456789" for "ccrep://homeRepository/abc-123-xyz-456789"
     *
     * @param string $url
     * @return string
     */
    public function getObjectIdFromUrl(string $url): string {
        $objectId = parse_url($url, PHP_URL_PATH);
        if ($objectId === false) {
            try {
                trigger_error(get_string('error_get_object_id_from_url', 'edusharing'), E_USER_WARNING);
            } catch (Exception $exception) {
                unset($exception);
                trigger_error('error_get_object_id_from_url', E_USER_WARNING);
            }
            return '';
        }

        return str_replace('/', '', $objectId);
    }

    /**
     * Function getRepositoryIdFromUrl
     *
     * Get the repository-id from object-url.
     * E.g. "homeRepository" for "ccrep://homeRepository/abc-123-xyz-456789"
     *
     * @param string $url
     * @return string
     * @throws Exception
     */
    public function getRepositoryIdFromUrl(string $url): string {
        $repoId = parse_url($url, PHP_URL_HOST);
        if ($repoId === false) {
            throw new Exception(get_string('error_get_repository_id_from_url', 'edusharing'));
        }

        return $repoId;
    }

    /**
     * Functions getRedirectUrl
     *
     * @throws Exception
     */
    public function getRedirectUrl(stdClass $eduSharing, string $displaymode = Constants::EDUSHARING_DISPLAY_MODE_DISPLAY): string {
        global $USER;
        $url = $this->getConfigEntry('application_cc_gui_url');
        $url .= '/renderingproxy';
        $url .= '?app_id=' . urlencode($this->getConfigEntry('application_appid'));
        $url .= '&session=' . urlencode(session_id());
        try {
            $repoId = $this->getRepositoryIdFromUrl($eduSharing->object_url);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return '';
        }
        $url     .= '&rep_id=' . urlencode($repoId);
        $url     .= '&obj_id=' . urlencode($this->getObjectIdFromUrl($eduSharing->object_url));
        $url     .= '&resource_id=' . urlencode($eduSharing->id);
        $url     .= '&course_id=' . urlencode($eduSharing->course);
        $context = context_course::instance($eduSharing->course);
        $roles   = get_user_roles($context, $USER->id);
        foreach ($roles as $role) {
            $url .= '&role=' . urlencode($role->shortname);
        }
        $url .= '&display=' . urlencode($displaymode);
        $url .= '&version=' . urlencode($eduSharing->object_version);
        $url .= '&locale=' . urlencode(current_language()); //repository
        $url .= '&language=' . urlencode(current_language()); //rendering service
        $url .= '&u=' . rawurlencode(base64_encode($this->encryptWithRepoKey($this->getAuthKey())));

        return $url;
    }

    /**
     * Function getAuthKey
     *
     * @throws dml_exception
     */
    public function getAuthKey(): string {
        global $USER, $SESSION;

        // Set by external sso script.
        if (!empty($SESSION->edusharing_sso)) {
            return $SESSION->edusharing_sso[$this->getConfigEntry('EDU_AUTH_PARAM_NAME_USERID')];
        }
        $guestOption = $this->getConfigEntry('edu_guest_option');
        if (!empty($guestOption)) {
            $guestId = $this->getConfigEntry('edu_guest_guest_id');

            return !empty($guestId) ? $guestId : 'esguest';
        }
        $eduAuthKey = $this->getConfigEntry('EDU_AUTH_KEY');
        if ($eduAuthKey == 'id')
            return $USER->id;
        if ($eduAuthKey == 'idnumber')
            return $USER->idnumber;
        if ($eduAuthKey == 'email')
            return $USER->email;
        if (isset($USER->profile[$eduAuthKey]))
            return $USER->profile[$eduAuthKey];
        return $USER->username;
    }

    /**
     * Function encryptWithRepoKey
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function encryptWithRepoKey(string $data): string {
        $encrypted = '';
        $key       = openssl_get_publickey($this->getConfigEntry('repository_public_key'));
        if (!openssl_public_encrypt($data, $encrypted, $key)) {
            trigger_error(get_string('error_encrypt_with_repo_public', 'edusharing'), E_USER_WARNING);
            return '';
        }
        return $encrypted;
    }

    /**
     * Function setModuleIdInDb
     *
     * @param string $text
     * @param array $data
     * @param string $id_type
     * @return void
     */
    public function setModuleIdInDb(string $text, array $data, string $id_type): void {
        global $DB;
        preg_match_all('#<img(.*)class="(.*)edusharing_atto(.*)"(.*)>#Umsi', $text, $matchesImgAtto, PREG_PATTERN_ORDER);
        preg_match_all('#<a(.*)class="(.*)edusharing_atto(.*)">(.*)</a>#Umsi', $text, $matchesAAtto, PREG_PATTERN_ORDER);
        $matchesAtto = array_merge($matchesImgAtto[0], $matchesAAtto[0]);
        foreach ($matchesAtto as $match) {
            $resourceId = '';
            $pos        = strpos($match, "resourceId=");
            if ($pos !== false) {
                $resourceId = substr($match, $pos + 11);
                $resourceId = substr($resourceId, 0, strpos($resourceId, "&"));
            }
            try {
                $DB->set_field('edusharing', $id_type, $data['objectid'], ['id' => $resourceId]);
            } catch (Exception $exception) {
                error_log('Could not set module_id: ' . $exception->getMessage());
            }
        }
    }

    /**
     * Function updateSettingsImages
     *
     * @param string $settingName
     * @return void
     */
    public function updateSettingsImages(string $settingName): void {
        global $CFG;
        // The setting name that was updated comes as a string like 's_theme_photo_loginbackgroundimage'.
        // We split it on '_' characters.
        $parts = explode('_', $settingName);
        // And get the last one to get the setting name..
        $settingName = end($parts);
        // Admin settings are stored in system context.
        try {
            $sysContext = context_system::instance();
            $filename   = $this->getConfigEntry($settingName);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return;
        }
        // This is the value of the admin setting which is the filename of the uploaded file.
        // We extract the file extension because we want to preserve it.
        $extension = substr($filename, strrpos($filename, '.') + 1);
        // This is the path in the moodle internal file system.
        $fullPath = "/{$sysContext->id}/" . 'edusharing' . "/{$settingName}/0{$filename}";
        // Get an instance of the moodle file storage.
        $fs = get_file_storage();
        // This is an efficient way to get a file if we know the exact path.
        if ($file = $fs->get_file_by_hash(sha1($fullPath))) {
            // We got the stored file - copy it to data root.
            // This location matches the searched for location in theme_config::resolve_image_location.
            $pathname = $CFG->dataroot . '/pix_plugins/mod/edusharing/icon.' . $extension;
            // This pattern matches any previous files with maybe different file extensions.
            $pathPattern = $CFG->dataroot . '/pix_plugins/mod/edusharing/icon.*';
            // Make sure this dir exists.
            @mkdir($CFG->dataroot . '/pix_plugins/mod/edusharing/', $CFG->directorypermissions, true);
            // Delete any existing files for this setting.
            foreach (glob($pathPattern) as $filename) {
                @unlink($filename);
            }
            // Copy the current file to this location.
            $file->copy_content_to($pathname);
        } else {
            $pathPattern = $CFG->dataroot . '/pix_plugins/mod/edusharing/icon.*';
            // Make sure this dir exists.
            @mkdir($CFG->dataroot . '/pix_plugins/mod/edusharing/', $CFG->directorypermissions, true);
            // Delete any existing files for this setting.
            foreach (glob($pathPattern) as $filename) {
                @unlink($filename);
            }
        }
        // Reset theme caches.
        theme_reset_all_caches();
    }

    /**
     * Function getCourseModuleInfo
     *
     * @param stdClass $courseModule
     * @return cached_cm_info|bool
     */
    public function getCourseModuleInfo(stdClass $courseModule): cached_cm_info|bool {
        global $DB;
        try {
            $edusharing = $DB->get_record('edusharing', ['id' => $courseModule->instance], 'id, name, intro, introformat', MUST_EXIST);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return false;
        }
        $info = new cached_cm_info();
        if ($courseModule->showdescription) {
            // Convert intro to html. Do not filter cached version, filters run at display time.
            $info->content = format_module_intro('edusharing', $edusharing, $courseModule->id, false);
        }
        try {
            $resource = $DB->get_record('edusharing', ['id' => $courseModule->instance], '*', MUST_EXIST);
            if (!empty($resource->popup_window)) {
                $info->onclick = 'this.target=\'_blank\';';
            }
        } catch (Exception $exception) {
            error_log($exception->getMessage());
        }
        return $info;
    }

    /**
     * Function getInlineObjectMatches
     *
     * @param string $inputText
     * @return array
     */
    public function getInlineObjectMatches(string $inputText): array {
        preg_match_all('#<img(.*)class="(.*)edusharing_atto(.*)"(.*)>#Umsi', $inputText, $matchesImg, PREG_PATTERN_ORDER);
        preg_match_all('#<a(.*)class="(.*)edusharing_atto(.*)">(.*)</a>#Umsi', $inputText, $matchesA, PREG_PATTERN_ORDER);
        return array_merge($matchesImg[0], $matchesA[0]);
    }

    /**
     * Function getConfigEntry
     *
     * @throws dml_exception
     */
    public function getConfigEntry(string $name): mixed {
        return $this->appConfig->get($name);
    }

    /**
     * Function setConfigEntry
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setConfigEntry(string $name, mixed $value): void {
        $this->appConfig->set($name, $value);
    }

    /**
     * Function getInternalUrl
     *
     * Retrieves the internal URL from config.
     *
     * @return string
     */
    public function getInternalUrl(): string {
        try {
            $internalUrl = $this->appConfig->get('application_docker_network_url');
            if (empty($internalUrl)) {
                $internalUrl = $this->appConfig->get('application_cc_gui_url');
            }
            return rtrim($internalUrl, '/');
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            unset($exception);
        }
        return '';
    }
}
