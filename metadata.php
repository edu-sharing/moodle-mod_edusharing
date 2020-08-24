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
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/mod/edusharing/locallib.php');

if (empty(get_config('edusharing', 'application_public_key'))) {
    require_once(dirname(__FILE__) . '/AppPropertyHelper.php');
    $modedusharingapppropertyhelper = new mod_edusharing_app_property_helper();
    $modedusharingapppropertyhelper->edusharing_add_ssl_keypair_to_home_config();
}

$metadata = createXmlMetadata();

header('Content-type: text/xml');
print($metadata);
exit();
