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

use JsonException;

/**
 * Class PluginRegistration
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class PluginRegistration {
    /**
     * @var EduSharingService
     */
    private EduSharingService $service;

    /**
     * PluginRegistration constructor
     *
     * @param EduSharingService $service
     */
    public function __construct(EduSharingService $service) {
        $this->service = $service;
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
    }

    /**
     * Function register_plugin
     *
     * @param string $repourl
     * @param string $login
     * @param string $pwd
     * @param string $data
     * @return array
     * @throws EduSharingUserException
     * @throws JsonException
     */
    public function register_plugin(string $repourl, string $login, string $pwd, string $data): array {
        $this->validate_alfresco_session($repourl, $login . ':' . $pwd);
        return $this->perform_registration($repourl, $data, $login . ':' . $pwd);
    }

    /**
     * Function validate_alfresco_session
     *
     * @param string $repourl
     * @param string $auth
     * @throws EduSharingUserException
     * @throws JsonException
     */
    private function validate_alfresco_session(string $repourl, string $auth): void {
        $result = $this->service->validate_session($repourl, $auth);
        if ($result->error !== 0) {
            throw new EduSharingUserException('API connection error');
        }
        $data = json_decode($result->content, true, 512, JSON_THROW_ON_ERROR);
        if (($data['isAdmin'] ?? false) === false) {
            throw new EduSharingUserException('Given user / password was not accepted as admin');
        }
    }

    /**
     * Function perform_registration
     *
     * @param string $repourl
     * @param string $data
     * @param string $auth
     * @return array
     * @throws EduSharingUserException
     * @throws JsonException
     */
    private function perform_registration(string $repourl, string $data, string $auth): array {
        $delimiter = '-------------' . uniqid();
        $body      = $this->get_registration_api_body($delimiter, $data);
        $result    = $this->service->register_plugin($repourl, $delimiter, $body, $auth);
        if ($result->error !== 0) {
            throw new EduSharingUserException('API connection error');
        }
        return json_decode($result->content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Function get_registration_api_body
     *
     * @param string $delimiter
     * @param string $data
     * @return string
     */
    private function get_registration_api_body(string $delimiter, string $data): string {
        $body = '--' . $delimiter . "\r\n";
        $body .= 'Content-Disposition: form-data; name="' . 'xml' . '"';
        $body .= '; filename="metadata.xml"' . "\r\n";
        $body .= 'Content-Type: text/xml' . "\r\n\r\n";
        $body .= $data . "\r\n";
        $body .= "--" . $delimiter . "--\r\n";

        return $body;
    }
}
