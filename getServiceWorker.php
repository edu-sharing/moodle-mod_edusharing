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

// phpcs:disable moodle.Files.MoodleInternal.MoodleInternalGlobalState

/**
 * getServiceWorker
 *
 * Proxy for service worker
 *
 * @package    filter_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

header('Content-Type: text/javascript');
header('Service-Worker-Allowed: /');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    $repositoryurl = rtrim(get_config('edusharing', 'application_docker_network_url'), '/');
    if (empty($repositoryurl)) {
        $repositoryurl = rtrim(get_config('edusharing', 'application_cc_gui_url'), '/');
    }
    if (empty($repositoryurl)) {
        http_response_code(500);
        echo '// Moodle plugin config "application_cc_gui_url" is not set.';
        exit;
    }
    $curl = new curl();
    $response = $curl->get($repositoryurl . '/web-components/rendering-service-amd/edu-service-worker.js');
    $info = $curl->get_info();
    if (!empty($info['http_code']) && $info['http_code'] >= 200 && $info['http_code'] < 300) {
        header('Content-Length: ' . strlen($response));
        echo $response;
    } else {
        http_response_code(502);
        echo '// Failed to fetch service worker from remote server. HTTP Code: ' . $info['http_code'];
    }
} catch (Exception $exception) {
    mtrace($exception);
    echo '// Internal server error';
    http_response_code(500);
}
