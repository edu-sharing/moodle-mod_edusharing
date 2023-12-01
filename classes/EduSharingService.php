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

use coding_exception;
use dml_exception;
use EduSharingApiClient\CurlResult;
use EduSharingApiClient\CurlHandler as EdusharingCurlHandler;
use EduSharingApiClient\EduSharingAuthHelper;
use EduSharingApiClient\EduSharingHelperBase;
use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\EduSharingNodeHelperConfig;
use EduSharingApiClient\NodeDeletedException;
use EduSharingApiClient\UrlHandling;
use EduSharingApiClient\Usage;
use EduSharingApiClient\UsageDeletedException;
use Exception;
use JsonException;
use moodle_exception;
use require_login_exception;
use stdClass;

/**
 * class EduSharingService
 *
 * Wrapper service class for API utilities bundled in the auth plugin
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 */
class EduSharingService {
    /**
     * @var EduSharingAuthHelper|null
     */
    private ?EduSharingAuthHelper $authhelper;
    /**
     * @var EduSharingNodeHelper|null
     */
    private ?EduSharingNodeHelper $nodehelper;
    /**
     * @var UtilityFunctions|null
     */
    private ?UtilityFunctions     $utils;

    /**
     * EduSharingService constructor
     *
     * constructor params are optional if you want to use DI.
     * This possibility is needed for unit testing
     *
     * @param EduSharingAuthHelper|null $authhelper
     * @param EduSharingNodeHelper|null $nodehelper
     * @param UtilityFunctions|null $utils
     * @throws dml_exception
     */
    public function __construct(
        ?EduSharingAuthHelper $authhelper = null,
        ?EduSharingNodeHelper $nodehelper = null,
        ?UtilityFunctions $utils = null
    ) {
        $this->authhelper = $authhelper;
        $this->nodehelper = $nodehelper;
        $this->utils      = $utils;
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
        $this->init();
    }

    /**
     * Function init
     *
     * @throws dml_exception
     * @throws Exception
     */
    private function init(): void {
        $this->utils === null && $this->utils = new UtilityFunctions();
        if ($this->authhelper === null || $this->nodehelper === null) {
            $internalurl = $this->utils->get_internal_url();
            $basehelper  = new EduSharingHelperBase(
                $internalurl,
                $this->utils->get_config_entry('application_private_key'),
                $this->utils->get_config_entry('application_appid')
            );
            $basehelper->registerCurlHandler(new MoodleCurlHandler());
            $this->authhelper === null && $this->authhelper = new EduSharingAuthHelper($basehelper);
            if ($this->nodehelper === null) {
                $nodeconfig       = new EduSharingNodeHelperConfig(new UrlHandling(true));
                $this->nodehelper = new EduSharingNodeHelper($basehelper, $nodeconfig);
            }
        }
    }

    /**
     * Function create_usage
     *
     * @param stdClass $usagedata
     * @return Usage
     * @throws JsonException
     */
    public function create_usage(stdClass $usagedata): Usage {
        return $this->nodehelper->createUsage(
            !empty($usagedata->ticket) ? $usagedata->ticket : $this->get_ticket(),
            (string)$usagedata->containerId,
            (string)$usagedata->resourceId,
            (string)$usagedata->nodeId,
            (string)$usagedata->nodeVersion
        );
    }

    /**
     * Function get_usage_id
     *
     * @param stdClass $usagedata
     * @return string
     * @throws Exception
     */
    public function get_usage_id(stdClass $usagedata): string {
        $usageid = $this->nodehelper->getUsageIdByParameters($usagedata->ticket,
            $usagedata->nodeId,
            $usagedata->containerId,
            $usagedata->resourceId
        );
        $usageid === null && throw new Exception('No usage found');
        return $usageid;
    }

    /**
     * Function delete_usage
     *
     * @param stdClass $usagedata
     * @throws Exception
     */
    public function delete_usage(stdClass $usagedata): void {
        !isset($usagedata->usageId) && throw new Exception('No usage id provided, deletion cannot be performed');
        try {
            $this->nodehelper->deleteUsage($usagedata->nodeId, $usagedata->usageId);
        } catch (UsageDeletedException $usagedeletedexception) {
            debugging('noted, deleting locally: ' . $usagedeletedexception->getMessage());
        }
    }

    /**
     * Function get_node
     *
     * @param object $postdata
     * @return array
     * @throws JsonException
     * @throws NodeDeletedException
     * @throws UsageDeletedException
     */
    public function get_node($postdata): array {
        $usage = new Usage(
            $postdata->nodeId,
            $postdata->nodeVersion,
            $postdata->containerId,
            $postdata->resourceId,
            $postdata->usageId
        );
        return $this->nodehelper->getNodeByUsage($usage);
    }

    /**
     * Function get_ticket
     *
     * @throws Exception
     */
    public function get_ticket(): string {
        global $USER;
        if (isset($USER->edusharing_userticket)) {
            if (isset($USER->edusharing_userticketvalidationts) && time() - $USER->edusharing_userticketvalidationts < 10) {
                return $USER->edusharing_userticket;
            }
            $ticketinfo = $this->authhelper->getTicketAuthenticationInfo($USER->edusharing_userticket);
            if ($ticketinfo['statusCode'] === 'OK') {
                $USER->edusharing_userticketvalidationts = time();

                return $USER->edusharing_userticket;
            }
        }
        $additionalfields = null;
        if ($this->utils->get_config_entry('send_additional_auth') === '1') {
            $additionalfields = [
                'firstName' => $USER->firstname,
                'lastName'  => $USER->lastname,
                'email'     => $USER->email,
            ];
        }
        return $this->authhelper->getTicketForUser($this->utils->get_auth_key(), $additionalfields);
    }

    /**
     * Function delete_instance
     *
     * Given an ID of an instance of this module,
     * this function will permanently delete the instance
     * and any data that depends on it.
     *
     * @param string $id
     * @return void
     * @throws dml_exception
     * @throws Exception
     */
    public function delete_instance(string $id): void {
        global $DB;
        $edusharing             = $DB->get_record('edusharing', ['id' => $id], '*', MUST_EXIST);
        $usagedata              = new stdClass();
        $usagedata->ticket      = $this->get_ticket();
        $usagedata->nodeId      = $this->utils->get_object_id_from_url($edusharing->object_url);
        $usagedata->containerId = $edusharing->course;
        $usagedata->resourceId  = $edusharing->id;
        $usagedata->usageId    = empty($edusharing->usage_id) ? $this->get_usage_id($usagedata) : $edusharing->usage_id;
        $this->delete_usage($usagedata);
        $DB->delete_records('edusharing', ['id' => $edusharing->id]);
    }

    /**
     * Function add_instance
     *
     * @param stdClass $edusharing
     * @param int|null $updatetime
     * @return bool|int
     */
    public function add_instance(stdClass $edusharing, ?int $updatetime = null): bool|int {
        global $DB;

        $edusharing->timecreated  = $updatetime ?? time();
        $edusharing->timemodified = $updatetime ?? time();

        // You may have to add extra stuff in here.
        $this->post_process_edusharing_object($edusharing, $updatetime);

        if (isset($_POST['object_version']) && $_POST['object_version'] != '0') {
            $edusharing->object_version = $_POST['object_version'];
        }
        // Use simple version handling for atto plugin or legacy code.
        if (isset($edusharing->editor_atto)) {
            // Avoid database error.
            $edusharing->introformat = 0;
        } else if (isset($edusharing->window_versionshow) && $edusharing->window_versionshow == 'current') {
            $edusharing->object_version = $edusharing->window_version;
        }
        try {
            $id = $DB->insert_record('edusharing', $edusharing);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return false;
        }
        $usagedata              = new stdClass();
        $usagedata->containerId = $edusharing->course;
        $usagedata->resourceId  = $id;
        $usagedata->nodeId      = $this->utils->get_object_id_from_url($edusharing->object_url);
        $usagedata->nodeVersion = $edusharing->object_version;
        try {
            $usage                = $this->create_usage($usagedata);
            $edusharing->id       = $id;
            $edusharing->usage_id = $usage->usageId;
            $DB->update_record('edusharing', $edusharing);
            return $id;
        } catch (Exception $exception) {
            !empty($exception->getMessage()) && debugging($exception->getMessage());
            try {
                $DB->delete_records('edusharing', ['id' => $id]);
            } catch (Exception $deleteexception) {
                debugging($deleteexception->getMessage());
            }
            return false;
        }
    }

    /**
     * Function update_instance
     *
     * @param stdClass $edusharing
     * @param int|null $updatetime
     * @return bool
     */
    public function update_instance(stdClass $edusharing, ?int $updatetime = null): bool {
        global $DB;
        // FIX: when editing a moodle-course-module the $edusharing->id will be named $edusharing->instance.
        if (!empty($edusharing->instance)) {
            $edusharing->id = $edusharing->instance;
        }
        $this->post_process_edusharing_object($edusharing, $updatetime);
        $usagedata              = new stdClass();
        $usagedata->containerId = $edusharing->course;
        $usagedata->resourceId  = $edusharing->id;
        $usagedata->nodeId      = $this->utils->get_object_id_from_url($edusharing->object_url);
        $usagedata->nodeVersion = $edusharing->object_version;
        try {
            $memento           = $DB->get_record('edusharing', ['id' => $edusharing->id], '*', MUST_EXIST);
            $usagedata->ticket = $this->get_ticket();
        } catch (Exception $exception) {
            unset($exception);
            return false;
        }
        try {
            $usage                = $this->create_usage($usagedata);
            $edusharing->usage_id = $usage->usageId;
            $DB->update_record('edusharing', $edusharing);
        } catch (Exception $exception) {
            !empty($exception->getMessage()) && debugging($exception->getMessage());
            try {
                $DB->update_record('edusharing', $memento);
            } catch (Exception $updateexception) {
                !empty($exception->getMessage()) && debugging($updateexception->getMessage());
            }
            return false;
        }
        return true;
    }

    /**
     * Function post_process_edusharing_object
     *
     * @param stdClass $edusharing
     * @param int|null $updatetime
     * @return void
     */
    private function post_process_edusharing_object(stdClass $edusharing, ?int $updatetime = null): void {
        if ($updatetime === null) {
            $updatetime = time();
        }
        global $COURSE;
        if (empty($edusharing->timecreated)) {
            $edusharing->timecreated = $updatetime;
        }
        $edusharing->timeupdated = $updatetime;
        if (!empty($edusharing->force_download)) {
            $edusharing->force_download = 1;
            $edusharing->popup_window   = 0;
        } else if (!empty($edusharing->popup_window)) {
            $edusharing->force_download = 0;
            $edusharing->options        = '';
        } else {
            if (empty($edusharing->blockdisplay)) {
                $edusharing->options = '';
            }
            $edusharing->popup_window = '';
        }
        $edusharing->tracking = empty($edusharing->tracking) ? 0 : $edusharing->tracking;
        if (!$edusharing->course) {
            $edusharing->course = $COURSE->id;
        }
    }

    /**
     * Function import_metadata
     *
     * @param string $url
     * @return CurlResult
     */
    public function import_metadata(string $url): CurlResult {
        $curloptions = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER         => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT'],
        ];
        return $this->authhelper->base->handleCurlRequest($url, $curloptions);
    }

    /**
     * Function validate_session
     *
     * @param string $url
     * @param string $auth
     * @return CurlResult
     */
    public function validate_session(string $url, string $auth): CurlResult {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($auth),
        ];
        $url     = rtrim($url, '/') . '/rest/authentication/v1/validateSession';
        return $this->authhelper->base->handleCurlRequest($url, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers,
        ]);
    }

    /**
     * Function register_plugin
     *
     * @param string $url
     * @param string $delimiter
     * @param string $body
     * @param string $auth
     * @return CurlResult
     */
    public function register_plugin(string $url, string $delimiter, string $body, string $auth): CurlResult {
        $registrationurl = rtrim($url, '/') . '/rest/admin/v1/applications/xml';
        $headers         = [
            'Content-Type: multipart/form-data; boundary=' . $delimiter,
            'Content-Length: ' . strlen($body),
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($auth),
        ];
        $this->authhelper->base->curlHandler->setMethod(EdusharingCurlHandler::METHOD_PUT);
        return $this->authhelper->base->handleCurlRequest($registrationurl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body,
        ]);
    }

    /**
     * Function sign
     *
     * @param string $input
     * @return string
     */
    public function sign(string $input): string {
        return $this->nodehelper->base->sign($input);
    }

    /**
     * Function get_render_html
     *
     * @param string $url
     * @return string
     */
    public function get_render_html(string $url): string {
        $curloptions = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER         => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT'],
        ];
        $result      = $this->authhelper->base->handleCurlRequest($url, $curloptions);
        if ($result->error !== 0) {
            try {
                return 'Unexpected Error';
            } catch (Exception $exception) {
                return $exception->getMessage();
            }
        }
        return $result->content;
    }

    /**
     * Function require_edu_login
     *
     * @param int|null $courseid
     * @param bool $checkticket
     * @param bool $checksessionkey
     * @throws coding_exception
     * @throws moodle_exception
     * @throws require_login_exception
     */
    public function require_edu_login(?int $courseid = null, bool $checkticket = true, bool $checksessionkey = true): void {
        require_login($courseid);
        $checksessionkey && require_sesskey();
        $checkticket && $this->get_ticket();
    }
}
