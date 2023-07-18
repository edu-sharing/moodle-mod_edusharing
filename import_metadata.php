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

use mod_edusharing\EduSharingUserException;
use mod_edusharing\MetaDataFrontend;
use mod_edusharing\MetadataLogic;
use mod_edusharing\PluginRegistrationFrontend;

global $CFG;

require_once(dirname(__FILE__, 3) . '/config.php');

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

if(isset($_POST['repoReg'])){
    if (!empty($_POST['appId'])){
        set_config('application_appid', $_POST['appId'], 'edusharing');
        error_log('appid set: '.$_POST['appId']);
    }
    if (!empty($_POST['host_aliases'])){
        set_config('application_host_aliases', $_POST['host_aliases'], 'edusharing');
    }
    echo PluginRegistrationFrontend::registerPlugin($_POST['repoUrl'], $_POST['repoAdmin'], $_POST['repoPwd']);
    exit();
}

$filename = '';
try {
    $metadataUrl = optional_param('mdataurl', '', PARAM_NOTAGS);
} catch (Exception $exception) {
    //This exception is stupid
    unset($exception);
}

if (! empty($metadataUrl)) {
    $service = new MetadataLogic();
    try {
        $service->importMetadata($metadataUrl);
        echo '<h3 class="edu_success">Import successful.</h3>';
    } catch (EduSharingUserException $eduSharingUserException) {
        echo $eduSharingUserException->getHtmlMessage();
    } catch (Exception $exception) {
        echo '<p style="background: #FF8170">Unexpected error - please try again later<br></p>';
    }
    if ($service->reloadForm) {
        echo MetaDataFrontend::getMetaDataForm();
    }
    $repoForm = MetaDataFrontend::getRepoForm();
    if ($repoForm !== null) {
        echo $repoForm;
    }
    exit();
}

echo MetaDataFrontend::getMetaDataForm();
$repoForm = MetaDataFrontend::getRepoForm();
if ($repoForm !== null) {
    echo $repoForm;
}

echo '</div></body></html>';
exit();
