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
 * Return app properties as XML
 *
 * @package mod_edusharing
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use EduSharingApiClient\EduSharingHelper;
use mod_edusharing\EduSharingService;
use mod_edusharing\MetadataLogic;
use mod_edusharing\UtilityFunctions;


require_once(dirname(__FILE__) . '/../../config.php');

$service = new EduSharingService();
$utils = new UtilityFunctions();

try {
    if ($utils->get_config_entry('require_login_for_metadata') == '1') {
        require_login();
    }
} catch (Exception $exception) {
    echo $exception->getMessage();
    unset($exception);
    exit();
}

try {
    $publickey = get_config('edusharing', 'application_public_key');
} catch (Exception $exception) {
    $publickey = '';
    unset($exception);
}

if (empty($publickey)) {
    try {
        $keypair = EduSharingHelper::generateKeyPair();
        set_config('application_public_key', $keypair['publicKey'], 'edusharing');
        set_config('application_private_key', $keypair['privateKey'], 'edusharing');
    } catch (Exception $exception) {
        debugging($exception->getMessage());
    }
}
$logic    = new MetadataLogic($service);
$metadata = $logic->create_xml_metadata();

header('Content-type: text/xml');
print($metadata);
exit();
