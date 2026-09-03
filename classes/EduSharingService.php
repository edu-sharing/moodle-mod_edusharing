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
use EduSharingApiClient\AppAuthException;
use EduSharingApiClient\CurlResult;
use EduSharingApiClient\CurlHandler as EdusharingCurlHandler;
use EduSharingApiClient\EduSharingAuthHelper;
use EduSharingApiClient\EduSharingHelperBase;
use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\EduSharingNodeHelperConfig;
use EduSharingApiClient\MissingRightsException;
use EduSharingApiClient\NodeDeletedException;
use EduSharingApiClient\SecuredNode;
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
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class EduSharingService {
    /**
     * Mimetypes of objects which are rendered at a fixed width of 100% and at a height
     * chosen by the user instead of at the dimensions reported by the repository.
     *
     * @see EduSharingService::uses_custom_height for the other objects treated this way.
     *
     * Keep in sync with CUSTOM_HEIGHT_MIMETYPES in mod_edusharing/utils (amd/src/utils.js).
     */
    public const CUSTOM_HEIGHT_MIMETYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.presentation',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/rtf',
        'application/vnd.oasis.opendocument.text-template',
        'text/plain',
    ];

    /**
     * Node properties that mark an object as serlo. Serlo objects are rendered like pdf documents.
     *
     * ccm:ccressourcetype is what the repository actually delivers for them. Objects that carry
     * the source in ccm:replicationsource instead are covered as well, because that is what the
     * rendering service identifies serlo by.
     *
     * Keep in sync with SERLO_PROPERTIES in mod_edusharing/utils (amd/src/utils.js).
     */
    public const SERLO_PROPERTIES = ['ccm:ccressourcetype', 'ccm:replicationsource'];

    /**
     * Property value marking a serlo object.
     */
    public const SERLO_SOURCE = 'serlo';

    /**
     * Node aspect marking an lti 1.3 tool object. Those are rendered like pdf documents too.
     *
     * Keep in sync with LTI_TOOL_ASPECT in mod_edusharing/utils (amd/src/utils.js).
     */
    public const LTI_TOOL_ASPECT = 'ccm:ltitool_node';

    /**
     * Types of remote repositories whose objects are rendered like pdf documents as well.
     *
     * Unlike serlo, these are not recognisable by a node property: their objects come from a
     * repository edu-sharing connects to as a whole, and that is what the rendering service
     * identifies them by.
     *
     * Keep in sync with CUSTOM_HEIGHT_REPOSITORY_TYPES in mod_edusharing/utils (amd/src/utils.js).
     */
    public const CUSTOM_HEIGHT_REPOSITORY_TYPES = ['learningapps', 'brockhaus'];

    /**
     * Range and fallback of the height the user may pick for a full width object.
     *
     * Keep in sync with CUSTOM_HEIGHT_MIN/MAX/DEFAULT in mod_edusharing/utils (amd/src/utils.js).
     */
    public const CUSTOM_HEIGHT_MIN = 300;
    /**
     * @see EduSharingService::CUSTOM_HEIGHT_MIN
     */
    public const CUSTOM_HEIGHT_MAX = 1200;
    /**
     * @see EduSharingService::CUSTOM_HEIGHT_MIN
     */
    public const CUSTOM_HEIGHT_DEFAULT = 600;

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
    private ?UtilityFunctions $utils;

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
        global $CFG;
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
                $nodeconfig = new EduSharingNodeHelperConfig(
                    new UrlHandling(
                        true,
                        $CFG->wwwroot . "/mod/edusharing/contentRedirect.php?sesskey=" . sesskey()
                    )
                );
                $this->nodehelper = new EduSharingNodeHelper($basehelper, $nodeconfig);
            }
            $basehelper->registerAboutApiCacheHandler(new MoodleAboutApiCacheHandler($this->nodehelper));
        }
    }

    /**
     * Function create_usage
     *
     * @param stdClass $usagedata
     * @return Usage
     * @throws JsonException
     * @throws MissingRightsException
     * @throws Exception
     */
    public function create_usage(stdClass $usagedata): Usage {
        return $this->nodehelper->createUsage(
            !empty($usagedata->ticket) ? $usagedata->ticket : $this->get_ticket(),
            (string)$usagedata->containerId,
            (string)$usagedata->resourceId,
            (string)$usagedata->nodeId,
            (string)$usagedata->nodeVersion,
            !empty($usagedata->courseTitle) ? (string)$usagedata->courseTitle : null
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
        $usageid = $this->nodehelper->getUsageIdByParameters(
            $usagedata->ticket,
            $usagedata->nodeId,
            $usagedata->containerId,
            $usagedata->resourceId
        );
        $usageid === null && throw new Exception('No usage found: ' . json_encode($usagedata));
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
     * @param Usage $usage
     * @param array|null $renderingparams
     * @param string|null $userid
     * @param bool $rendering2
     * @return array
     * @throws JsonException
     * @throws NodeDeletedException
     * @throws UsageDeletedException
     */
    public function get_node(Usage $usage, ?array $renderingparams = null, ?string $userid = null, bool $rendering2 = false): array {
        return $this->nodehelper->getNodeByUsage(
            usage: $usage,
            renderingParams: $renderingparams,
            userId: $userid,
            rendering2: $rendering2
        );
    }

    /**
     * Function get_redirect_url
     *
     * @param Usage $usage
     * @param string|null $userid
     * @param string $mode
     * @return string
     * @throws JsonException
     * @throws NodeDeletedException
     * @throws UsageDeletedException
     */
    public function get_redirect_url(Usage $usage, ?string $userid = null, string $mode = 'content'): string {
        return $this->nodehelper->getRedirectUrl($mode, $usage, [], $userid, $this->has_rendering_2());
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
     * Function clear_ticket_cache
     *
     * Discards the ticket cached in the current user's session.
     *
     * Must be called whenever the repository registration changes: a ticket
     * issued by the previously registered repository is not valid at the new
     * one, and revalidating it throws instead of falling back to a new ticket.
     *
     * @return void
     */
    public static function clear_ticket_cache(): void {
        global $USER;
        unset($USER->edusharing_userticket, $USER->edusharing_userticketvalidationts);
    }

    /**
     * Function get_ticket_for_user
     *
     * Get authentication ticket for a specific user
     *
     * @param stdClass $user
     * @return string
     * @throws dml_exception
     * @throws AppAuthException
     */
    public function get_ticket_for_user(stdClass $user): string {
        if ($this->utils->get_config_entry('send_additional_auth') === '1') {
            $additionalfields = [
                'firstName' => $user->firstname,
                'lastName'  => $user->lastname,
                'email'     => $user->email,
            ];
        } else {
            $additionalfields = null;
        }
        return $this->authhelper->getTicketForUser($this->utils->get_auth_key($user), $additionalfields);
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
        $usagedata->usageId     = empty($edusharing->usage_id) ? $this->get_usage_id($usagedata) : $edusharing->usage_id;
        $this->delete_usage($usagedata);
        $attemptids = $DB->get_fieldset_select('edusharing_attempts', 'id', 'edusharingid = ?', [$id]);
        if ($attemptids) {
            $DB->delete_records_list('edusharing_attempts', 'id', $attemptids);
        }
        $this->delete_grade_item($edusharing);

        $DB->delete_records('edusharing', ['id' => $edusharing->id]);
    }

    /**
     * Function delete_grade_item
     *
     * Removes the gradebook item associated with the given edusharing instance.
     *
     * @param stdClass $edusharing
     * @return void
     */
    public function delete_grade_item(stdClass $edusharing): void {
        edusharing_grade_item_delete($edusharing);
    }

    /**
     * Function add_instance
     *
     * Inserts the edusharing entry and registers the corresponding usage in the repository.
     *
     * If the usage cannot be created, the freshly inserted entry is removed again and the
     * original exception is rethrown, so callers can tell the user why it failed.
     *
     * @param stdClass $edusharing
     * @param int|null $updatetime
     * @return int
     * @throws coding_exception
     * @throws dml_exception
     * @throws MissingRightsException
     * @throws Exception
     */
    public function add_instance(stdClass $edusharing, ?int $updatetime = null): int {
        global $DB;

        $edusharing->timecreated  = $updatetime ?? time();
        $edusharing->timemodified = $updatetime ?? time();

        // You may have to add extra stuff in here.
        $this->post_process_edusharing_object($edusharing, $updatetime);

        $version = optional_param('object_version', '0', PARAM_TEXT);
        if ($version != '0') {
            $edusharing->object_version = $version;
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
            throw $exception;
        }
        $usagedata              = new stdClass();
        $usagedata->containerId = $edusharing->course;
        $usagedata->resourceId  = $id;
        $usagedata->nodeId      = $this->utils->get_object_id_from_url($edusharing->object_url);
        $usagedata->nodeVersion = $edusharing->object_version;
        $usagedata->courseTitle = $this->utils->get_course_title((int)$edusharing->course);
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
            throw $exception;
        }
    }

    /**
     * Function update_instance
     *
     * Updates the entry and refreshes the corresponding usage in the repository.
     *
     * If the usage cannot be refreshed, the previously working object is kept while every
     * other change the user made is still persisted, and the original exception is rethrown
     * so the caller can tell the user why the object stayed as it was.
     *
     * @param stdClass $edusharing
     * @param int|null $updatetime
     * @return void
     * @throws dml_exception
     * @throws MissingRightsException
     * @throws Exception
     */
    public function update_instance(stdClass $edusharing, ?int $updatetime = null): void {
        global $DB;
        // FIX: when editing a moodle-course-module the $edusharing->id will be named $edusharing->instance.
        if (!empty($edusharing->instance)) {
            $edusharing->id = $edusharing->instance;
        }
        $memento = $DB->get_record('edusharing', ['id' => $edusharing->id], '*', MUST_EXIST);
        // The edit form does not include object_url / object_version; keep the stored values.
        if (empty($edusharing->object_url)) {
            $edusharing->object_url = $memento->object_url;
        }
        if (!isset($edusharing->object_version)) {
            $edusharing->object_version = $memento->object_version;
        }
        $this->post_process_edusharing_object($edusharing, $updatetime);
        $usagedata              = new stdClass();
        $usagedata->containerId = $edusharing->course;
        $usagedata->resourceId  = $edusharing->id;
        $usagedata->nodeId      = $this->utils->get_object_id_from_url($edusharing->object_url);
        $usagedata->nodeVersion = $edusharing->object_version;
        $usagedata->courseTitle = $this->utils->get_course_title((int)$edusharing->course);
        try {
            $usagedata->ticket    = $this->get_ticket();
            $usage                = $this->create_usage($usagedata);
            $edusharing->usage_id = $usage->usageId;
            $DB->update_record('edusharing', $edusharing);
        } catch (Exception $exception) {
            !empty($exception->getMessage()) && debugging($exception->getMessage());
            // Keep the object that is known to work, but save everything else the user changed.
            $edusharing->usage_id       = $memento->usage_id;
            $edusharing->object_url     = $memento->object_url;
            $edusharing->object_version = $memento->object_version;
            try {
                $DB->update_record('edusharing', $edusharing);
            } catch (Exception $updateexception) {
                !empty($updateexception->getMessage()) && debugging($updateexception->getMessage());
            }
            throw $exception;
        }
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
     * @throws dml_exception
     */
    public function import_metadata(string $url): CurlResult {
        $curloptions = [
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER         => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];
        if ($this->utils->get_config_entry('allow_registration_over_http') != '1') {
            $curloptions[CURLOPT_SSL_VERIFYPEER] = false;
            $curloptions[CURLOPT_SSL_VERIFYHOST] = false;
        }
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
     * @throws Exception
     */
    public function sign(string $input): string {
        return $this->nodehelper->base->sign(toSign: $input);
    }

    /**
     * Function get_render_html
     *
     * @param string $url
     * @return string
     * @throws dml_exception
     */
    public function get_render_html(string $url): string {
        $curloptions = [
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER         => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT'],
        ];
        if ($this->utils->get_config_entry('allow_registration_over_http') != '1') {
            $curloptions[CURLOPT_SSL_VERIFYPEER] = false;
            $curloptions[CURLOPT_SSL_VERIFYHOST] = false;
        }
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
     * @throws Exception
     */
    public function require_edu_login(?int $courseid = null, bool $checkticket = true, bool $checksessionkey = true): void {
        require_login($courseid);
        $checksessionkey && require_sesskey();
        $checkticket && $this->get_ticket();
    }

    /**
     * Function get_preview_image
     *
     * @param Usage $usage
     * @param string $userid
     * @return CurlResult
     */
    public function get_preview_image(Usage $usage, string $userid): CurlResult {
        return $this->nodehelper->getPreview(usage: $usage, userid: $userid);
    }

    /**
     * Function get_secured_node
     *
     * @param Usage $usage
     * @return SecuredNode
     * @throws JsonException
     * @throws dml_exception
     */
    public function get_secured_node(Usage $usage): SecuredNode {
        global $CFG;
        $securednode = $this->nodehelper->getSecuredNodeByUsage($usage, $this->utils->get_auth_key());
        $securednode->previewUrl = $CFG->wwwroot . '/mod/edusharing/preview.php?resourceId=' . $usage->resourceId;
        $securednode->signingAlgorithm = $this->get_signing_algorithm();
        return $securednode;
    }

    /**
     * Function get_rendering_2_url
     *
     * @throws JsonException
     * @throws Exception
     */
    public function get_rendering_2_url(): string {
        $about = $this->nodehelper->base->getAboutCached();
        if (isset($about['renderingService2']['url'])) {
            return $about['renderingService2']['url'];
        }
        throw new Exception('Rendering Service 2 is not configured');
    }

    /**
     * Function has_rendering_2
     *
     * @return bool
     */
    public function has_rendering_2(): bool {
        try {
            $this->get_rendering_2_url();
            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Function get_signing_algorithm
     *
     * @return string
     * @throws JsonException
     */
    public function get_signing_algorithm(): string {
        return $this->nodehelper->base->getAlgorithm();
    }

    /**
     * Function get_custom_width
     *
     * @param array $node
     * @return string
     */
    public function get_custom_width(array $node): string {
        if ($this->is_youtube_node($node)) {
            return 'none';
        }
        if ($this->uses_custom_height($node)) {
            return '100%';
        }
        return '';
    }

    /**
     * Function uses_custom_height
     *
     * Pdf-like documents, serlo objects, lti 1.3 tool objects and objects from a learningapps
     * or brockhaus repository are rendered at a fixed width of 100%. For those the user picks
     * the height, so the height stored with the object has to be applied on rendering instead
     * of being left to the rendering service.
     *
     * @param array $node
     * @return bool
     */
    public function uses_custom_height(array $node): bool {
        if ($this->is_youtube_node($node)) {
            return false;
        }
        if (in_array($node['mimetype'] ?? '', self::CUSTOM_HEIGHT_MIMETYPES, true)) {
            return true;
        }
        if (in_array($this->get_remote_repository_type($node), self::CUSTOM_HEIGHT_REPOSITORY_TYPES, true)) {
            return true;
        }
        return $this->is_serlo_node($node) || $this->has_aspect($node, self::LTI_TOOL_ASPECT);
    }

    /**
     * Function get_remote_repository_type
     *
     * The type of the remote repository a node was fetched from, lower cased and trimmed for
     * comparison. Empty for nodes of the connected repository itself.
     *
     * @param array $node
     * @return string
     */
    private function get_remote_repository_type(array $node): string {
        $type = $node['remote']['repository']['repositoryType'] ?? '';
        return is_string($type) ? strtolower(trim($type)) : '';
    }

    /**
     * Function has_aspect
     *
     * Aspects are exact qualified names, so they are compared as a whole - unlike the property
     * values in is_serlo_node, which have variants to cover.
     *
     * @param array $node
     * @param string $aspect
     * @return bool
     */
    private function has_aspect(array $node, string $aspect): bool {
        $aspects = $node['aspects'] ?? [];
        if (!is_array($aspects)) {
            $aspects = [$aspects];
        }
        foreach ($aspects as $value) {
            if (is_string($value) && strtolower(trim($value)) === strtolower($aspect)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Function is_serlo_node
     *
     * The repository reports node properties as string arrays and a property may well carry
     * more than one value, so every entry is looked at. A plain string is tolerated too.
     * Values are matched by prefix, so variants such as 'serlo_spider' count as serlo as well.
     *
     * @param array $node
     * @return bool
     */
    private function is_serlo_node(array $node): bool {
        foreach (self::SERLO_PROPERTIES as $property) {
            $values = $node['properties'][$property] ?? [];
            if (!is_array($values)) {
                $values = [$values];
            }
            foreach ($values as $value) {
                if (is_string($value) && str_starts_with(strtolower(trim($value)), self::SERLO_SOURCE)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Function clamp_custom_height
     *
     * Restricts a height picked by the user to the allowed range. Anything unusable falls
     * back to the default height instead of to a broken layout.
     *
     * @param mixed $value
     * @return int
     */
    public function clamp_custom_height(mixed $value): int {
        if (!is_numeric($value)) {
            return self::CUSTOM_HEIGHT_DEFAULT;
        }
        return min(self::CUSTOM_HEIGHT_MAX, max(self::CUSTOM_HEIGHT_MIN, (int)$value));
    }

    /**
     * Function is_youtube_node
     *
     * @param array $node
     * @return bool
     */
    private function is_youtube_node(array $node): bool {
        if ($this->get_remote_repository_type($node) === 'youtube') {
            return true;
        }
        $url = $node['properties']['ccm:wwwurl'][0] ?? '';
        return str_contains($url, 'youtu.be') || str_contains($url, 'youtube.com/watch?');
    }
}
