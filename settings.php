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
 * This file defines the edu-sharing settings
 *
 * @package mod_edusharing
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $ADMIN;

if ($ADMIN->fulltree) {
    $hint = '<div class="form-defaultinfo small text-muted " style="margin-top: 6px">'
        . get_string('conf_hinttext', 'edusharing') . '</div>';
    $hint = '';
    $str  = '<div class="form-item row">
                <div class="form-label col-sm-3 text-sm-right">
                    <p>' . get_string('conf_linktext', 'edusharing') . '</p>
                </div>
                <div class="form-setting col-sm-9">
                    <div class="form-text defaultsnext">
                        <a class="btn btn-primary" style="margin-top: 5px;" href="' . $CFG->wwwroot .
        '/mod/edusharing/import_metadata.php?sesskey=' . $USER->sesskey . '" target="_blank">' .
        get_string('conf_btntext', 'edusharing') . '</a>
                    </div>' . $hint . '
                </div>
        </div>';
    // phpcs:disable -- just messy html
    $strversion = '<div class="form-item row">
                <div class="form-label col-sm-3 text-sm-right">
                    <p>' . get_string('conf_versiontext', 'edusharing') . '</p>
                </div>
                <div class="form-setting col-sm-9">
                    <div class="form-text defaultsnext">
                        <div class="form-defaultinfo">Release: ' . get_config('mod_edusharing', 'version') . '</div>
                        <div class="form-defaultinfo small text-muted " style="margin-top: 6px">Release' . get_config('mod_edusharing', 'release') . '</div>
                    </div>' . $hint . '
                </div>
        </div>';
    // phpcs:enable
    $settings->add(new admin_setting_heading('edusharing', get_string('currentVersion', 'edusharing'), $strversion));
    $settings->add(new admin_setting_heading('edusharing/repo', get_string('connectToHomeRepository', 'edusharing'), $str));
    $settings->add(new admin_setting_heading('edusharing/app', get_string('appProperties', 'edusharing'), ''));
    $settings->add(new admin_setting_configtext('edusharing/application_appid', 'appid', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/application_type', 'type', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/application_homerepid', 'homerepid', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/application_host_aliases', 'host_aliases', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/application_cc_gui_url', 'cc_gui_url', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/application_docker_network_url',
        'docker_network_url', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtextarea('edusharing/application_private_key', 'private_key', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtextarea('edusharing/application_public_key', 'public_key', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_heading('edusharing/rep', get_string('homerepProperties', 'edusharing'), ''));
    $settings->add(new admin_setting_configtextarea('edusharing/repository_public_key', 'public_key', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/repository_clientport', 'clientport', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/repository_port', 'port', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/repository_domain', 'domain', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/repository_type', 'type', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/repository_appid', 'appid', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/repository_protocol', 'protocol', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/repository_host', 'host', '', '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/repository_version', 'version', '', '5.1', PARAM_TEXT, 50));
    $settings->add(new admin_setting_heading('edusharing/auth', get_string('authparameters', 'edusharing'), ''));
    $settings->add(new admin_setting_configtext('edusharing/EDU_AUTH_KEY', 'EDU_AUTH_KEY',
        '', 'username', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/EDU_AUTH_PARAM_NAME_USERID',
        'PARAM_NAME_USERID', '', 'userid', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/EDU_AUTH_PARAM_NAME_LASTNAME',
        'PARAM_NAME_LASTNAME', '', 'lastname', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/EDU_AUTH_PARAM_NAME_FIRSTNAME',
        'PARAM_NAME_FIRSTNAME', '', 'firstname', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/EDU_AUTH_PARAM_NAME_EMAIL',
        'PARAM_NAME_EMAIL', '', 'email', PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/EDU_AUTH_AFFILIATION',
        'AFFILIATION', '', $CFG->siteidentifier, PARAM_TEXT, 50));
    $settings->add(new admin_setting_configtext('edusharing/EDU_AUTH_AFFILIATION_NAME',
        'AFFILIATION_NAME', '', $CFG->siteidentifier, PARAM_TEXT, 50));
    $settings->add(new admin_setting_configcheckbox('edusharing/EDU_AUTH_CONVEYGLOBALGROUPS', 'CONVEYGLOBALGROUPS', '', ''));
    $settings->add(
        new admin_setting_configcheckbox('edusharing/send_additional_auth',
            new lang_string('send_additional_auth', 'edusharing'),
            new lang_string('send_additional_auth_help', 'edusharing'),
            '1'
        )
    );
    $settings->add(
        new admin_setting_configcheckbox('edusharing/obfuscate_auth_param',
            new lang_string('obfuscate_auth_param', 'edusharing'),
            new lang_string('obfuscate_auth_param_help', 'edusharing'),
            '0'
        )
    );
    $settings->add(new admin_setting_configtext('edusharing/auth_suffix',
        new lang_string('auth_suffix', 'edusharing'),
         new lang_string('auth_suffix_help', 'edusharing'),
        '', PARAM_TEXT, 50));
    $settings->add(new admin_setting_heading('edusharing/guest', get_string('guestProperties', 'edusharing'), ''));
    $settings->add(new admin_setting_configcheckbox('edusharing/edu_guest_option', 'guest_option', '', ''));
    $settings->add(new admin_setting_configcheckbox('edusharing/wlo_guest_option', 'wlo_guest_option', '', ''));
    $settings->add(new admin_setting_configtext('edusharing/edu_guest_guest_id', 'guest_id', '', 'esguest', PARAM_TEXT, 50));
    // UI settings.
    $settings->add(new admin_setting_heading('edusharing/branding',
        get_string('brandingSettings', 'edusharing'),
        get_string('brandingInfo', 'edusharing')));
    $namesetting = new admin_setting_configtext('edusharing/application_appname', 'appname', '', 'edu-sharing', PARAM_TEXT, 50);
    $namesetting->set_updatedcallback('edusharing_update_settings_name');
    $settings->add($namesetting);
    $typesetting = new admin_setting_configtext('edusharing/module_type', 'type', '', 'Objekt', PARAM_TEXT, 50);
    $typesetting->set_updatedcallback('edusharing_update_settings_name');
    $settings->add($typesetting);
    $imgsetting = new admin_setting_configstoredfile('edusharing/appicon', 'appicon',
        get_string('appiconDescr', 'edusharing'), 'appicon');
    $imgsetting->set_updatedcallback('edusharing_update_settings_images');
    $settings->add($imgsetting);
    $infosetting = new admin_setting_configtextarea('edusharing/info_text', 'info_text',
        get_string('info_textDescr', 'edusharing'), 'Hallo');
    $infosetting->set_updatedcallback('edusharing_update_settings_name');
    $settings->add($infosetting);
    $hintsetting = new admin_setting_configtextarea('edusharing/atto_hint', 'atto_hint',
        get_string('atto_hintDescr', 'edusharing'), '');
    $hintsetting->set_updatedcallback('edusharing_update_settings_name');
    $settings->add($hintsetting);
    $hintsetting = new admin_setting_configtextarea('edusharing/atto_hint', 'atto_hint',
        get_string('atto_hintDescr', 'edusharing'), '');
    $hintsetting->set_updatedcallback('edusharing_update_settings_name');
    $settings->add($hintsetting);
    $repotargetoptions = [
        'search'      => get_string('repoSearch', 'edusharing'),
        'collections' => get_string('repoCollection', 'edusharing'),
        'workspace'   => get_string('repoWorkspace', 'edusharing'),
    ];
    $repotargetsetting = new admin_setting_configselect('edusharing/repo_target', 'repo_target',
        get_string('repo_targetDescr', 'edusharing'), 'search', $repotargetoptions);
    $settings->add($repotargetsetting);
    $settings->add(new admin_setting_heading('edusharing/save',
        get_string('save', 'edusharing'), ''));
}
