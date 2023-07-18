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
use mod_edusharing\MetadataLogic;

require_once(dirname(__FILE__) . '/../../config.php');

if (empty(get_config('edusharing', 'application_public_key'))) {
    try {
        $keyPair = EduSharingHelper::generateKeyPair();
    } catch (Exception $exception) {
        error_log($exception->getMessage());
    }
    set_config('application_public_key', $keyPair['publicKey'], 'edusharing');
    set_config('application_private_key', $keyPair['privateKey'], 'edusharing');
}
$logic    = new MetadataLogic();
$metadata = $logic->createXmlMetadata();

header('Content-type: text/xml');
print($metadata);
exit();
