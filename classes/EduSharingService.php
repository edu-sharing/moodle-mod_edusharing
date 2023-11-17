<?php declare(strict_types=1);

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
 */
class EduSharingService
{
    private ?EduSharingAuthHelper $authHelper;
    private ?EduSharingNodeHelper $nodeHelper;
    private ?UtilityFunctions     $utils;

    /**
     * EduSharingService constructor
     *
     * constructor params are optional if you want to use DI.
     * This possibility is needed for unit testing
     *
     * @throws dml_exception
     * @throws Exception
     */
    public function __construct(?EduSharingAuthHelper $authHelper = null, ?EduSharingNodeHelper $nodeHelper = null, ?UtilityFunctions $utils = null) {
        $this->authHelper = $authHelper;
        $this->nodeHelper = $nodeHelper;
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
        if ($this->authHelper === null || $this->nodeHelper === null) {
            $internalUrl = $this->utils->getInternalUrl();
            $baseHelper  = new EduSharingHelperBase($internalUrl, $this->utils->getConfigEntry('application_private_key'), $this->utils->getConfigEntry('application_appid'));
            $baseHelper->registerCurlHandler(new MoodleCurlHandler());
            $this->authHelper === null && $this->authHelper = new EduSharingAuthHelper($baseHelper);
            if ($this->nodeHelper === null) {
                $nodeConfig       = new EduSharingNodeHelperConfig(new UrlHandling(true));
                $this->nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
            }
        }
    }

    /**
     * Function createUsage
     *
     * @throws JsonException
     * @throws Exception
     */
    public function createUsage(stdClass $usageData): Usage {
        return $this->nodeHelper->createUsage(!empty($usageData->ticket) ? $usageData->ticket : $this->getTicket(), (string)$usageData->containerId, (string)$usageData->resourceId, (string)$usageData->nodeId, (string)$usageData->nodeVersion);
    }

    /**
     * Function getUsageId
     *
     * @throws Exception
     */
    public function getUsageId(stdClass $usageData): string {
        $usageId = $this->nodeHelper->getUsageIdByParameters($usageData->ticket, $usageData->nodeId, $usageData->containerId, $usageData->resourceId);
        $usageId === null && throw new Exception('No usage found');
        return $usageId;
    }

    /**
     * Function deleteUsage
     *
     * @throws Exception
     */
    public function deleteUsage(stdClass $usageData): void {
        !isset($usageData->usageId) && throw new Exception('No usage id provided, deletion cannot be performed');
        try {
            $this->nodeHelper->deleteUsage($usageData->nodeId, $usageData->usageId);
        } catch (UsageDeletedException $usageDeletedException) {
            error_log('noted, deleting locally: ' . $usageDeletedException->getMessage());
        }
    }

    /**
     * Function getNode
     *
     * @throws NodeDeletedException
     * @throws UsageDeletedException
     * @throws JsonException
     */
    public function getNode($postData): array {
        $usage = new Usage($postData->nodeId, $postData->nodeVersion, $postData->containerId, $postData->resourceId, $postData->usageId);
        return $this->nodeHelper->getNodeByUsage($usage);
    }

    /**
     * Function getTicket
     *
     * @throws Exception
     */
    public function getTicket(): string {
        global $USER;
        if (isset($USER->edusharing_userticket)) {
            if (isset($USER->edusharing_userticketvalidationts) && time() - $USER->edusharing_userticketvalidationts < 10) {
                return $USER->edusharing_userticket;
            }
            $ticketInfo = $this->authHelper->getTicketAuthenticationInfo($USER->edusharing_userticket);
            if ($ticketInfo['statusCode'] === 'OK') {
                $USER->edusharing_userticketvalidationts = time();

                return $USER->edusharing_userticket;
            }
        }
        $additionalFields = null;
        if ($this->utils->getConfigEntry('send_additional_auth') === '1') {
            $additionalFields = [
                'firstName' => $USER->firstname,
                'lastName'  => $USER->lastname,
                'email'     => $USER->email
            ];
        }
        return $this->authHelper->getTicketForUser($this->utils->getAuthKey(), $additionalFields);
    }

    /**
     * Function deleteInstance
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
    public function deleteInstance(string $id): void {
        global $DB;
        $eduSharing             = $DB->get_record('edusharing', ['id' => $id], '*', MUST_EXIST);
        $usageData              = new stdClass();
        $usageData->ticket      = $this->getTicket();
        $usageData->nodeId      = $this->utils->getObjectIdFromUrl($eduSharing->object_url);
        $usageData->containerId = $eduSharing->course;
        $usageData->resourceId  = $eduSharing->id;
        $usageData->usageId    = empty($edusharing->usage_id) ? $this->getUsageId($usageData) : $edusharing->usage_id;
        $this->deleteUsage($usageData);
        $DB->delete_records('edusharing', ['id' => $eduSharing->id]);
    }

    /**
     * Function addInstance
     *
     * @param stdClass $eduSharing
     * @param int|null $updateTime
     * @return bool|int
     */
    public function addInstance(stdClass $eduSharing, ?int $updateTime = null): bool|int {
        global $DB;

        $eduSharing->timecreated  = $updateTime ?? time();
        $eduSharing->timemodified = $updateTime ?? time();

        // You may have to add extra stuff in here.
        $this->postProcessEdusharingObject($eduSharing, $updateTime);

        if (isset($_POST['object_version']) && $_POST['object_version'] != '0') {
            $eduSharing->object_version = $_POST['object_version'];
        }
        //use simple version handling for atto plugin or legacy code
        if (isset($eduSharing->editor_atto)) {
            //avoid database error
            $eduSharing->introformat = 0;
        } else if (isset($eduSharing->window_versionshow) && $eduSharing->window_versionshow == 'current') {
            $eduSharing->object_version = $eduSharing->window_version;
        }
        try {
            $id = $DB->insert_record('edusharing', $eduSharing);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return false;
        }
        $usageData              = new stdClass();
        $usageData->containerId = $eduSharing->course;
        $usageData->resourceId  = $id;
        $usageData->nodeId      = $this->utils->getObjectIdFromUrl($eduSharing->object_url);
        $usageData->nodeVersion = $eduSharing->object_version;
        try {
            $usage                = $this->createUsage($usageData);
            $eduSharing->id       = $id;
            $eduSharing->usage_id = $usage->usageId;
            $DB->update_record('edusharing', $eduSharing);
            return $id;
        } catch (Exception $exception) {
            !empty($exception->getMessage()) && error_log($exception->getMessage());
            try {
                $DB->delete_records('edusharing', ['id' => $id]);
            } catch (Exception $deleteException) {
                error_log($deleteException->getMessage());
            }
            return false;
        }
    }

    /**
     * Function updateInstance
     *
     * @param stdClass $edusharing
     * @param int|null $updateTime
     * @return bool
     */
    public function updateInstance(stdClass $edusharing, ?int $updateTime = null): bool {
        global $DB;
        // FIX: when editing a moodle-course-module the $edusharing->id will be named $edusharing->instance
        if (!empty($edusharing->instance)) {
            $edusharing->id = $edusharing->instance;
        }
        $this->postProcessEdusharingObject($edusharing, $updateTime);
        $usageData              = new stdClass();
        $usageData->containerId = $edusharing->course;
        $usageData->resourceId  = $edusharing->id;
        $usageData->nodeId      = $this->utils->getObjectIdFromUrl($edusharing->object_url);
        $usageData->nodeVersion = $edusharing->object_version;
        try {
            $memento           = $DB->get_record('edusharing', ['id' => $edusharing->id], '*', MUST_EXIST);
            $usageData->ticket = $this->getTicket();
        } catch (Exception $exception) {
            unset($exception);
            return false;
        }
        try {
            $usage                = $this->createUsage($usageData);
            $edusharing->usage_id = $usage->usageId;
            $DB->update_record('edusharing', $edusharing);
        } catch (Exception $exception) {
            !empty($exception->getMessage()) && error_log($exception->getMessage());
            try {
                $DB->update_record('edusharing', $memento);
            } catch (Exception $updateException) {
                !empty($exception->getMessage()) && error_log($updateException->getMessage());
            }
            return false;
        }
        return true;
    }

    /**
     * Function postProcessEdusharingObject
     *
     * @param stdClass $edusharing
     * @param int|null $updateTime
     * @return void
     */
    private function postProcessEdusharingObject(stdClass $edusharing, ?int $updateTime = null): void {
        if ($updateTime === null) {
            $updateTime = time();
        }
        global $COURSE;
        if (empty($edusharing->timecreated)) {
            $edusharing->timecreated = $updateTime;
        }
        $edusharing->timeupdated = $updateTime;
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
     * Function importMetadata
     *
     * @param string $url
     * @return CurlResult
     */
    public function importMetadata(string $url): CurlResult {
        $curlOptions = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER         => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT']
        ];
        return $this->authHelper->base->handleCurlRequest($url, $curlOptions);
    }

    /**
     * Function validateSession
     *
     * @param string $url
     * @param string $auth
     * @return CurlResult
     */
    public function validateSession(string $url, string $auth): CurlResult {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($auth)
        ];
        $url     = rtrim($url, '/') . '/rest/authentication/v1/validateSession';
        return $this->authHelper->base->handleCurlRequest($url, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers
        ]);
    }

    /**
     * Function registerPlugin
     *
     * @param string $url
     * @param string $delimiter
     * @param string $body
     * @param string $auth
     * @return CurlResult
     */
    public function registerPlugin(string $url, string $delimiter, string $body, string $auth): CurlResult {
        $registrationUrl = rtrim($url, '/') . '/rest/admin/v1/applications/xml';
        $headers         = [
            'Content-Type: multipart/form-data; boundary=' . $delimiter,
            'Content-Length: ' . strlen($body),
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($auth)
        ];
        $this->authHelper->base->curlHandler->setMethod(EdusharingCurlHandler::METHOD_PUT);
        return $this->authHelper->base->handleCurlRequest($registrationUrl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body
        ]);
    }

    /**
     * Function sign
     *
     * @param string $input
     * @return string
     */
    public function sign(string $input): string {
        return $this->nodeHelper->base->sign($input);
    }

    /**
     * Function getRenderHtml
     *
     * @param string $url
     * @return string
     */
    public function getRenderHtml(string $url): string {
        $curlOptions = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER         => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT']
        ];
        $result      = $this->authHelper->base->handleCurlRequest($url, $curlOptions);
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
     * Function requireEduLogin
     *
     * @throws require_login_exception
     * @throws coding_exception
     * @throws moodle_exception
     * @throws Exception
     */
    public function requireEduLogin(?int $courseId = null, bool $checkTicket = true, bool $checkSessionKey = true): void {
        require_login($courseId);
        $checkSessionKey && require_sesskey();
        $checkTicket && $this->getTicket();
    }
}
