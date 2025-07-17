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

// phpcs:ignoreFile

/**
 * English strings for edusharing
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['searchrec'] = 'Search the {$a} repository ...';
$string['uploadrec'] = 'Upload to {$a} repository ...';
$string['pagewindow'] = 'In-page display';
$string['newwindow'] = 'Display in new window';
$string['display'] = 'Display';
// Modulename seems to be used in admin-panels,
// Pluginname seems to be used in course-view.
try {
    $string['modulename'] = get_config('edusharing', 'application_appname') . ' ' . get_config('edusharing', 'module_type');
    $string['modulename_help'] = get_config('edusharing', 'info_text');
} catch (Exception $exception) {
    $string['modulename'] = '';
    $string['modulename_help'] = '';
    unset($exception);
}
$string['pluginname'] = 'edu-sharing resource';
$string['modulenameplural'] = 'edu-sharing';
$string['edusharing'] = 'edu-sharing';
$string['pluginadministration'] = 'edu-sharing';
$string['edusharingname'] = 'Title';
$string['description'] = 'Description';

$string['edusharing:wysiwygvisibility'] = 'Add, edit and remove Edu-Sharing content in WYSIWYG editors.';

$string['object_url_fieldset'] = '{$a} Learning-object';
$string['object_url'] = 'Object';
$string['object_url_help'] = 'Please use the buttons below to select an object from repository. Its object-ID will be inserted here automatically.';
$string['object_title'] = 'Selected object';
$string['object_title_help'] = 'Please use the button below to select an object.';
$string['object_title_help_chooser'] = 'Please use one of the buttons below to select an object.';

$string['edusharing:addinstance'] = 'Add instance.';

$string['object_version_fieldset'] = 'Object-versioning';
$string['object_version'] = 'Use ..';
$string['object_version_help'] = 'Select which Object-version to use.';
$string['object_version_use_exact'] = 'Use selected object-version.';
$string['object_version_use_latest'] = 'Use latest object-version';

$string['object_display_fieldset'] = 'Object-display options';
$string['object_display_fieldset_help'] = '';

$string['force_download'] = 'Force download';
$string['force_download_help'] = 'Force object-download.';

$string['show_course_blocks'] = 'Show course-blocks';
$string['show_course_blocks_help'] = 'Show course-blocks in target-window.';

$string['window_allow_resize'] = 'Allow resizing';
$string['window_allow_resize_help'] = 'Allow resizing of target-window.';

$string['window_allow_scroll'] = 'Allow scrolling';
$string['window_allow_scroll_help'] = 'Allow scrolling in target-window.';

$string['show_directory_links'] = 'Show directory-links';
$string['show_directory_links_help'] = 'Show directory-links.';

$string['show_menu_bar'] = 'Show menu-bar';
$string['show_menu_bar_help'] = 'Show menu-bar in target-window.';

$string['show_location_bar'] = 'Show location-bar';
$string['show_location_bar_help'] = 'Show location-bar in target-window.';

$string['show_tool_bar'] = 'Show tool-bar';
$string['show_tool_bar_help'] = 'Show toolbar in target-window.';

$string['show_status_bar'] = 'Show status-bar';
$string['show_status_bar_help'] = 'Show status-bar in target-window.';

$string['window_width'] = 'Display-width';
$string['window_width_help'] = 'Width of target-window.';

$string['window_height'] = 'Display-height';
$string['window_height_help'] = 'Height for target-window.';

// General error message.
$string['exc_MESSAGE'] = 'An error occured utilizing the edu-sharing.net network.';

// Beautiful exceptions.
$string['exc_SENDACTIVATIONLINK_SUCCESS'] = 'Successfully sent activation-link.';
$string['exc_APPLICATIONACCESS_NOT_ACTIVATED_BY_USER'] = 'Access not activated by user.';
$string['exc_COULD_NOT_CONNECT_TO_HOST'] = 'Could not connect to host.';
$string['exc_INTEGRITY_VIOLATION'] = 'Integrity violation.';
$string['exc_INVALID_APPLICATION'] = 'Invalid application.';
$string['exc_ERROR_FETCHING_HTTP_HEADERS'] = 'Error fetching HTTP-headers.';
$string['exc_NODE_DOES_NOT_EXIST'] = 'Node does not exist anymore.';
$string['exc_ACCESS_DENIED'] = 'Access denied.';
$string['exc_NO_PERMISSION'] = 'Insufficient permissions.';
$string['exc_UNKNOWN_ERROR'] = 'Unknown error.';
$string['exc_NO_PUBLISH_RIGHTS'] = 'An edu-sharing object could not be restored due to missing publish rights and will be skipped.';

// Metadata.
$string['currentVersion'] = 'Current plugin version';
$string['conf_versiontext'] = 'Version:';
$string['connectToHomeRepository'] = 'Connect to Home Reposiory';
$string['conf_linktext'] = 'Connect moodle to home repository:';
$string['conf_btntext'] = 'Connect';
$string['conf_hinttext'] = 'This will open a new window where you can load the repository metadata and register the plugin with the repository.';
$string['appProperties'] = 'Application Properties';
$string['homerepProperties'] = 'Home Repository Properties';
$string['authparameters'] = 'Authentication Parameters';
$string['guestProperties'] = 'Guest properties';
$string['brandingSettings'] = 'UI settings';
$string['brandingInfo'] = 'Change the look & feel of the edu-sharing plugin.';
$string['appiconDescr'] = 'This icon replaces the edu-sharing icon (including the atto-button).';
$string['info_textDescr'] = 'The helptext for adding the edu-sharing module.';
$string['atto_hintDescr'] = 'The helptext for the edus-haring atto-popup.';
$string['repo_targetDescr'] = 'Configure the startpage in the repository';
$string['enable_repo_target_chooser'] = 'Start page selectable by user';
$string['enable_repo_target_chooser_help'] = 'If this option is enabled, the user can select in the GUI which entry page should be displayed in the repository.';
$string['repoSearch'] = 'Search';
$string['repoCollection'] = 'Collections';
$string['repoWorkspace'] = 'My Files';

$string['save'] = 'Save changes';
$string['emptyForDefault'] = 'empty for default';
$string['filter_not_authorized'] = 'You are not authorized to access the requested content.';

// Auth parameters.
$string['convey_global_groups_yes'] = 'Convey cohorts';
$string['convey_global_groups_no'] = 'Do not convey cohorts';
$string['send_additional_auth'] = 'Send additional auth information';
$string['send_additional_auth_help'] = 'If checked, the app authentication request will include the first and last name as well as email address.';
$string['auth_suffix'] = 'Authentication suffix';
$string['auth_suffix_help'] = 'If configured, this suffix will be added to the submitted auth string';
$string['obfuscate_auth_param'] = 'User ID pseudonymization';
$string['obfuscate_auth_param_help'] = 'If activated, Moodle users will be pseudonymized in the Edu-Sharing repository';
$string['require_login_for_metadata'] = 'Login required for metadata';
$string['require_login_for_metadata_help'] = 'If activated, app metadata can only be queried after login';

$string['soaprequired'] = 'The PHP extension soap must be activated.';

$string['error_missing_authwsdl'] = 'No "authenticationwebservice_wsdl" configured.';
$string['error_authservice_not_reachable'] = 'not reachable. Cannot utilize edu-sharing network.';
$string['error_invalid_ticket'] = 'Invalid ticket. Cannot utilize edu-sharing network.';
$string['error_auth_failed'] = 'Cannot utilize edu-sharing network because authentication failed.';
$string['error_load_course'] = 'Cannot load course from database.';
$string['error_load_resource'] = 'Cannot load resource from database.';
$string['error_get_object_id_from_url'] = 'Cannot get object id from url.';
$string['error_get_repository_id_from_url'] = 'Cannot get repository id from url.';
$string['error_detect_course'] = 'Cannot detect course id';
$string['error_loading_memento'] = 'Error loading temporary object.';
$string['error_set_soap_headers'] = 'Cannot set SOAP headers - ';
$string['error_get_app_properties'] = 'Cannot load plugin config';
$string['error_encrypt_with_repo_public'] = 'Cannot encrypt data.';
$string['error_missing_rights_on_restore'] = 'This Edu-Sharing object is missing due to insufficient user rights during course restoration.';
$string['error_unexpected_on_restore'] = 'This Edu-Sharing object is missing due to an unexpected error having occurred during restoration';
$string['error_parsing_on_restore'] = 'This Edu-Sharing object is missing due to a parsing error having occurred during restoration';
