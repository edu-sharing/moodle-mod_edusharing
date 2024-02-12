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
 * Get repository properties and generate app properties - put them to configuration
 *
 * @package mod_edusharing
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @todo Implement as moustache template
 */

use mod_edusharing\EduSharingService;
use mod_edusharing\EduSharingUserException;
use mod_edusharing\MetaDataFrontend;
use mod_edusharing\MetadataLogic;
use mod_edusharing\PluginRegistrationFrontend;
use mod_edusharing\UtilityFunctions;

global $CFG;

require_once(dirname(__FILE__, 3) . '/config.php');
require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');

echo '<html>
<head>
    <title>edu-sharing metadata import</title>
    <link rel="stylesheet" href="import_metadata_style.css" />
</head>
<body>
<div class="h5p-header">
    <h1>Import metadata from an edu-sharing repository</h1>
</div>
<div class="wrap">';
if (!is_siteadmin()) {
    echo '<h3>Access denied!</h3>';
    echo '<p>Please login with your admin account in moodle.</p>';
    exit();
}


if (isset($_POST['repoReg'])) {
    if (!empty($_POST['appId'])) {
        set_config('application_appid', $_POST['appId'], 'edusharing');
    }
    if (!empty($_POST['host_aliases'])) {
        set_config('application_host_aliases', $_POST['host_aliases'], 'edusharing');
    }
    echo PluginRegistrationFrontend::register_plugin($_POST['repoUrl'], $_POST['repoAdmin'], $_POST['repoPwd']);
    exit();
}

$filename = '';
try {
    $metadataurl = optional_param('mdataurl', '', PARAM_NOTAGS);
} catch (Exception $exception) {
    // This exception is stupid.
    unset($exception);
}

if (!empty($metadataurl)) {
    try {
        $utils = new UtilityFunctions();
        $appid = $utils->get_config_entry('application_appid');
        if (empty($appid)) {
            $utils->set_config_entry('application_appid', uniqid('moodle_'));
        }
        $service = new MetadataLogic(new EduSharingService());
        $service->import_metadata($metadataurl);
        echo '<h3 class="edu_success">Import successful.</h3>';
    } catch (EduSharingUserException $edusharinguserexception) {
        echo $edusharinguserexception->get_html_message();
    } catch (Exception $exception) {
        echo '<p style="background: #FF8170">Unexpected error - please try again later<br></p>';
    }
    if ($service->reloadform) {
        echo MetaDataFrontend::get_meta_data_form();
    }
    $repoform = MetaDataFrontend::get_repo_form();
    if ($repoform !== null) {
        echo $repoform;
    }
    exit();
}

echo MetaDataFrontend::get_meta_data_form();
$repoform = MetaDataFrontend::get_repo_form();
if ($repoform !== null) {
    echo $repoform;
}

echo '</div></body></html>';
exit();
