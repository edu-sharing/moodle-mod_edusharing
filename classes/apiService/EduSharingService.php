<?php declare(strict_types = 1);

namespace mod_edusharing\apiService;

use dml_exception;
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
use mod_edusharing\UtilityFunctions;
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
    private EduSharingAuthHelper $authHelper;
    private EduSharingNodeHelper $nodeHelper;

    /**
     * EduSharingService constructor
     *
     * @throws dml_exception
     * @throws Exception
     */
    public function __construct() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
        $this->init();
    }

    /**
     * Function init
     * Sets up all dependencies (Yes, we SHOULD use DI, but it's simply impractical within moodle)
     *
     * @throws dml_exception
     * @throws Exception
     */
    private function init(): void {
        $baseHelper = new EduSharingHelperBase(get_config('edusharing', 'application_cc_gui_url'), get_config('edusharing', 'application_private_key'), get_config('edusharing', 'application_appid'));
        $baseHelper->registerCurlHandler(new MoodleCurlHandler());
        $this->authHelper = new EduSharingAuthHelper($baseHelper);
        $nodeConfig       = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $this->nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
    }

    /**
     * Function createUsage
     *
     * @throws JsonException
     * @throws Exception
     */
    public function createUsage(stdClass $usageData): Usage {
        return $this->nodeHelper->createUsage($this->getTicket(), $usageData->containerId, $usageData->resourceId, $usageData->nodeId, $usageData->nodeVersion);
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
        ! isset($usageData->usageId) && throw new Exception('No usage id provided, deletion cannot be performed');
        try {
            $this->nodeHelper->deleteUsage($usageData->nodeId, $usageData->usageId);
        } catch (UsageDeletedException $usageDeletedException) {
            error_log( 'noted, deleting locally: ' . $usageDeletedException->getMessage());
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
            try {
                $ticketInfo = $this->authHelper->getTicketAuthenticationInfo($USER->edusharing_userticket);
            } catch (Exception $exception) {
                unset($exception);
            }
            if (isset($ticketInfo) && $ticketInfo['statusCode'] === 'OK' ) {
                $USER->edusharing_userticketvalidationts = time();

                return $USER->edusharing_userticket;
            }
        }
        return $this->authHelper->getTicketForUser(UtilityFunctions::getAuthKey());
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
        $eduSharing = $DB->get_record(EDUSHARING_TABLE, ['id'  => $id], '*', MUST_EXIST);
        $endpoint   = get_config('edusharing', 'repository_restApi');
        if (empty($endpoint)) {
            throw new Exception('Deletion cannot be performed, REST endpoint is undefined');
        }
        $usageData              = new stdClass();
        $usageData->ticket      = $this->getTicket();
        $usageData->nodeId      = UtilityFunctions::getObjectIdFromUrl($eduSharing->object_url);
        $usageData->containerId = $eduSharing->containerId;
        $usageData->resourceId  = $eduSharing->resourceId;
        $usageData->usage_id    = empty($edusharing->usage_id) ? $this->getUsageId($usageData) : $edusharing->usage_id;
        $this->deleteUsage($usageData);
        $DB->delete_records(EDUSHARING_TABLE, ['id' => $eduSharing->id]);
    }
}
