<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Fetches object preview from repository
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use EduSharingApiClient\Usage;
use mod_edusharing\EduSharingService;
use mod_edusharing\UtilityFunctions;

global $DB;

require_once(dirname(__FILE__, 3) . '/config.php');

try {
    require_login();
    $resourceid = optional_param('resourceId', 0, PARAM_INT);
    $edusharing = $DB->get_record('edusharing', ['id' => $resourceid], '*', MUST_EXIST);
    $utils      = new UtilityFunctions();
    $service    = new EduSharingService();
    $usage      = new Usage(
        $utils->get_object_id_from_url($edusharing->object_url),
        $edusharing->object_version,
        (string)$edusharing->course,
        (string)$edusharing->id,
        (string)$edusharing->usage_id
    );
    $curlresult = $service->get_preview_image($usage);
} catch (Exception $exception) {
    echo 'Error occurred: ' . $exception->getMessage();
    exit();
}

header('Content-type: ' . $curlresult->info['content_type']);
echo $curlresult->content;
exit();
