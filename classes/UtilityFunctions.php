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
 * @package mod_edusharing
 */
class UtilityFunctions {
    /**
     * @var AppConfig|null
     */
    private ?AppConfig $appconfig;

    /**
     * UtilityFunctions constructor
     *
     * @param AppConfig|null $config
     */
    public function __construct(?AppConfig $config = null) {
        $this->appconfig = $config;
        $this->init();
    }

    /**
     * Function init
     *
     * @return void
     */
    private function init(): void {
        if ($this->appconfig === null) {
            $this->appconfig = new DefaultAppConfig();
        }
    }

    /**
     * Function get_object_id_from_url
     *
     * Get the object-id from object-url.
     * E.g. "abc-123-xyz-456789" for "ccrep://homeRepository/abc-123-xyz-456789"
     *
     * @param string $url
     * @return string
     */
    public function get_object_id_from_url(string $url): string {
        $objectid = parse_url($url, PHP_URL_PATH);
        if ($objectid === false) {
            try {
                trigger_error(get_string('error_get_object_id_from_url', 'edusharing'), E_USER_WARNING);
            } catch (Exception $exception) {
                unset($exception);
                trigger_error('error_get_object_id_from_url', E_USER_WARNING);
            }
            return '';
        }

        return str_replace('/', '', $objectid);
    }

    /**
     * Function get_repository_id_from_url
     *
     * Get the repository-id from object-url.
     * E.g. "homeRepository" for "ccrep://homeRepository/abc-123-xyz-456789"
     *
     * @param string $url
     * @return string
     * @throws Exception
     */
    public function get_repository_id_from_url(string $url): string {
        $repoid = parse_url($url, PHP_URL_HOST);
        if ($repoid === false) {
            throw new Exception(get_string('error_get_repository_id_from_url', 'edusharing'));
        }

        return $repoid;
    }

    /**
     * Functions get_redirect_url
     *
     * @param stdClass $edusharing
     * @param string $displaymode
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_redirect_url(
        stdClass $edusharing,
        string $displaymode = Constants::EDUSHARING_DISPLAY_MODE_DISPLAY
    ): string {
        global $USER;
        $url = $this->get_config_entry('application_cc_gui_url');
        $url .= '/renderingproxy';
        $url .= '?app_id=' . urlencode($this->get_config_entry('application_appid'));
        $url .= '&session=' . urlencode(session_id());
        try {
            $repoid = $this->get_repository_id_from_url($edusharing->object_url);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return '';
        }
        $url     .= '&rep_id=' . urlencode($repoid);
        $url     .= '&obj_id=' . urlencode($this->get_object_id_from_url($edusharing->object_url));
        $url     .= '&resource_id=' . urlencode($edusharing->id);
        $url     .= '&course_id=' . urlencode($edusharing->course);
        $context = context_course::instance($edusharing->course);
        $roles   = get_user_roles($context, $USER->id);
        foreach ($roles as $role) {
            $url .= '&role=' . urlencode($role->shortname);
        }
        $url .= '&display=' . urlencode($displaymode);
        $url .= '&version=' . urlencode($edusharing->object_version);
        $url .= '&locale=' . urlencode(current_language());
        $url .= '&language=' . urlencode(current_language());
        $url .= '&u=' . rawurlencode(base64_encode($this->encrypt_with_repo_key($this->get_auth_key())));

        return $url;
    }

    /**
     * Function get_auth_key
     *
     * @throws dml_exception
     */
    public function get_auth_key(): string {
        global $USER, $SESSION;

        // Set by external sso script.
        if (!empty($SESSION->edusharing_sso)) {
            return $SESSION->edusharing_sso[$this->get_config_entry('EDU_AUTH_PARAM_NAME_USERID')];
        }
        $guestoption = $this->get_config_entry('edu_guest_option');
        if (!empty($guestoption)) {
            $guestid = $this->get_config_entry('edu_guest_guest_id');

            return !empty($guestid) ? $guestid : 'esguest';
        }
        $eduauthkey = $this->get_config_entry('EDU_AUTH_KEY');
        if ($eduauthkey == 'id') {
            return $USER->id;
        }
        if ($eduauthkey == 'idnumber') {
            return $USER->idnumber;
        }
        if ($eduauthkey == 'email') {
            return $USER->email;
        }
        if (isset($USER->profile[$eduauthkey])) {
            return $USER->profile[$eduauthkey];
        }
        return $USER->username;
    }

    /**
     * Function encrypt_with_repo_key
     *
     * @param string $data
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function encrypt_with_repo_key(string $data): string {
        $encrypted = '';
        $key       = openssl_get_publickey($this->get_config_entry('repository_public_key'));
        if (!openssl_public_encrypt($data, $encrypted, $key)) {
            trigger_error(get_string('error_encrypt_with_repo_public', 'edusharing'), E_USER_WARNING);
            return '';
        }
        return $encrypted;
    }

    /**
     * Function set_module_id_in_db
     *
     * @param string $text
     * @param array $data
     * @param string $idtype
     * @return void
     */
    public function set_module_id_in_db(string $text, array $data, string $idtype): void {
        global $DB;
        preg_match_all('#<img(.*)class="(.*)edusharing_atto(.*)"(.*)>#Umsi',
            $text, $matchesimgatto, PREG_PATTERN_ORDER);
        preg_match_all('#<a(.*)class="(.*)edusharing_atto(.*)">(.*)</a>#Umsi',
            $text, $matchesaatto, PREG_PATTERN_ORDER);
        $matchesatto = array_merge($matchesimgatto[0], $matchesaatto[0]);
        foreach ($matchesatto as $match) {
            $resourceid = '';
            $pos        = strpos($match, "resourceId=");
            if ($pos !== false) {
                $resourceid = substr($match, $pos + 11);
                $resourceid = substr($resourceid, 0, strpos($resourceid, "&"));
            }
            try {
                $DB->set_field('edusharing', $idtype, $data['objectid'], ['id' => $resourceid]);
            } catch (Exception $exception) {
                debugging('Could not set module_id: ' . $exception->getMessage());
            }
        }
    }

    /**
     * Function update_settings_images
     *
     * @param string $settingname
     * @return void
     */
    public function update_settings_images(string $settingname): void {
        global $CFG;
        // The setting name that was updated comes as a string like 's_theme_photo_loginbackgroundimage'.
        // We split it on '_' characters.
        $parts = explode('_', $settingname);
        // And get the last one to get the setting name..
        $settingname = end($parts);
        // Admin settings are stored in system context.
        try {
            $syscontext = context_system::instance();
            $filename   = $this->get_config_entry($settingname);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return;
        }
        // This is the value of the admin setting which is the filename of the uploaded file.
        // We extract the file extension because we want to preserve it.
        $extension = substr($filename, strrpos($filename, '.') + 1);
        // This is the path in the moodle internal file system.
        $fullpath = "/{$syscontext->id}/" . 'edusharing' . "/{$settingname}/0{$filename}";
        // Get an instance of the moodle file storage.
        $fs = get_file_storage();
        // This is an efficient way to get a file if we know the exact path.
        if ($file = $fs->get_file_by_hash(sha1($fullpath))) {
            // We got the stored file - copy it to data root.
            // This location matches the searched for location in theme_config::resolve_image_location.
            $pathname = $CFG->dataroot . '/pix_plugins/mod/edusharing/icon.' . $extension;
            // This pattern matches any previous files with maybe different file extensions.
            $pathpattern = $CFG->dataroot . '/pix_plugins/mod/edusharing/icon.*';
            // Make sure this dir exists.
            @mkdir($CFG->dataroot . '/pix_plugins/mod/edusharing/', $CFG->directorypermissions, true);
            // Delete any existing files for this setting.
            foreach (glob($pathpattern) as $filename) {
                @unlink($filename);
            }
            // Copy the current file to this location.
            $file->copy_content_to($pathname);
        } else {
            $pathpattern = $CFG->dataroot . '/pix_plugins/mod/edusharing/icon.*';
            // Make sure this dir exists.
            @mkdir($CFG->dataroot . '/pix_plugins/mod/edusharing/', $CFG->directorypermissions, true);
            // Delete any existing files for this setting.
            foreach (glob($pathpattern) as $filename) {
                @unlink($filename);
            }
        }
        // Reset theme caches.
        theme_reset_all_caches();
    }

    /**
     * Function get_course_module_info
     *
     * @param stdClass $coursemodule
     * @return cached_cm_info|bool
     */
    public function get_course_module_info(stdClass $coursemodule): cached_cm_info|bool {
        global $DB;
        try {
            $edusharing = $DB->get_record(
                'edusharing',
                ['id' => $coursemodule->instance],
                'id, name, intro, introformat',
                MUST_EXIST
            );
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return false;
        }
        $info = new cached_cm_info();
        if ($coursemodule->showdescription) {
            // Convert intro to html. Do not filter cached version, filters run at display time.
            $info->content = format_module_intro('edusharing', $edusharing, $coursemodule->id, false);
        }
        try {
            $resource = $DB->get_record('edusharing', ['id' => $coursemodule->instance], '*', MUST_EXIST);
            if (!empty($resource->popup_window)) {
                $info->onclick = 'this.target=\'_blank\';';
            }
        } catch (Exception $exception) {
            debugging($exception->getMessage());
        }
        return $info;
    }

    /**
     * Function get_inline_object_matches
     *
     * @param string $inputtext
     * @return array
     */
    public function get_inline_object_matches(string $inputtext): array {
        preg_match_all('#<img(.*)class="(.*)edusharing_atto(.*)"(.*)>#Umsi', $inputtext, $matchesimg, PREG_PATTERN_ORDER);
        preg_match_all('#<a(.*)class="(.*)edusharing_atto(.*)">(.*)</a>#Umsi', $inputtext, $matchesa, PREG_PATTERN_ORDER);
        return array_merge($matchesimg[0], $matchesa[0]);
    }

    /**
     * Function get_config_entry
     *
     * @param string $name
     * @return mixed
     * @throws dml_exception
     */
    public function get_config_entry(string $name): mixed {
        return $this->appconfig->get($name);
    }

    /**
     * Function set_config_entry
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set_config_entry(string $name, mixed $value): void {
        $this->appconfig->set($name, $value);
    }

    /**
     * Function get_internal_url
     *
     * Retrieves the internal URL from config.
     *
     * @return string
     */
    public function get_internal_url(): string {
        try {
            $internalurl = $this->appconfig->get('application_docker_network_url');
            if (empty($internalurl)) {
                $internalurl = $this->appconfig->get('application_cc_gui_url');
            }
            return rtrim($internalurl, '/');
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            unset($exception);
        }
        return '';
    }
}
