<?php
global $CFG;

require_once $CFG->dirroot . "/mod/edusharing/includes/EduSharingApiClient/edu-sharing-plugin/edu-sharing-helper.php";
require_once $CFG->dirroot . "/mod/edusharing/includes/EduSharingApiClient/edu-sharing-plugin/edu-sharing-helper-base.php";
require_once $CFG->dirroot . "/mod/edusharing/includes/EduSharingApiClient/edu-sharing-plugin/edu-sharing-auth-helper.php";
require_once $CFG->dirroot . "/mod/edusharing/includes/EduSharingApiClient/edu-sharing-plugin/edu-sharing-node-helper.php";

class EduSharingService {

    public $config;
    public $helperBase;
    private $authHelper;

    public function __construct() {
        $this -> helperBase = new EduSharingHelperBase(  get_config('edusharing', 'application_cc_gui_url'), get_config('edusharing', 'application_private_key'), get_config('edusharing', 'application_appid') );
        $this -> authHelper = new EduSharingAuthHelper( $this->helperBase );

        $this -> helperBase->registerCurlHandler(new class extends CurlHandler {

            public function handleCurlRequest(string $url, array $curlOptions): CurlResult {

                $curl = new curl();
                $options = array();
                $params = '';
                $post = false;
                foreach ($curlOptions as $key => $value){
                    if ($key == 'CURLOPT_HTTPHEADER'){
                        $curl->header = $value;
                    }else if ($key == 'CURLOPT_POSTFIELDS'){
                        $params = $value;
                    }else if ($key == 'CURLOPT_POST' && $value == 1){
                        $post = true;
                    }else{
                        $options[$key] = $value;
                    }
                }

                if ($post){
                    $curlContent = $curl->post($url, $params, $options);
                }else{
                    $curlContent = $curl->get($url, $params, $options);
                }

                $curlError = $curl->error;
                if (empty($curlError)){
                    $curlError = 0;
                }
                return new CurlResult($curlContent, $curlError, $curl->info);
            }
        });

    }

    public function createUsage( $usageData)  {

        $nodeHelper = new EduSharingNodeHelper( $this->helperBase );
        $result = $nodeHelper->createUsage(
            $usageData->ticket,
            $usageData->containerId,
            $usageData->resourceId,
            $usageData->nodeId,
            $usageData->nodeVersion
        );
        return $result;

    }

    public function getUsageId( $usageData)  {

        $nodeHelper = new EduSharingNodeHelper( $this->helperBase );
        $result = $nodeHelper->getUsageIdByParameters(
            $usageData->ticket,
            $usageData->nodeId,
            $usageData->containerId,
            $usageData->resourceId
        );
        return $result;

    }

    public function deleteUsage( $usageData ) {
        if (!isset($usageData->usageId)){
            return false;
        }
        $nodeHelper = new EduSharingNodeHelper($this->helperBase);
        try {
            $result = $nodeHelper->deleteUsage($usageData->nodeId, $usageData->usageId);
            return $result;

        } catch ( Exception $e ) {
            if ( $e instanceof UsageDeletedException ) {
                error_log( 'noted, deleting locally: ' . $e->getMessage() );
            } else {
                throw $e;
            }
        }
    }

    public function getNode($postData) {
        $nodeHelper = new EduSharingNodeHelper($this->helperBase);
        try {
            $result = $nodeHelper->getNodeByUsage(
                new Usage(
                    $postData->nodeId,
                    $postData->nodeVersion,
                    $postData->containerId,
                    $postData->resourceId,
                    $postData->usageId
                )
            );
            return $result;

        } catch ( Exception $e ) {
            throw $e;
        }
    }

    public function getTicket() {
        global $USER;

        // Ticket available.
        if (isset($USER->edusharing_userticket)) {

            // Ticket is younger than 10s, we must not check.
            if (isset($USER->edusharing_userticketvalidationts)
                && time() - $USER->edusharing_userticketvalidationts < 10) {
                return $USER->edusharing_userticket;
            }

            // Ticket is older than 10s.
            // check if ticket is still valid
            try {
                $ticketInfo = $this->authHelper->getTicketAuthenticationInfo( $USER->edusharing_userticket );
            } catch ( Exception $e ) {
                // something went wrong, e.g. cached ticket is not valid, so get a new one
                trigger_error(get_string('error_invalid_ticket', 'edusharing'), E_USER_WARNING);
            }

            if ( isset( $ticketInfo ) && $ticketInfo['statusCode'] == 'OK' ) {
                $USER->edusharing_userticketvalidationts = time();
                return $USER->edusharing_userticket;
            }

        }

        // No or invalid ticket available -> request new ticket.
        $ticket = null;
        try {
            $ticket = $this->authHelper->getTicketForUser( edusharing_get_auth_key() );
        } catch (Exception $e) {
            error_log( "Couldn't get ticket from Edusharing repository ($e)" );
            trigger_error(get_string('error_auth_failed', 'edusharing') . ' ' . $e, E_USER_WARNING);
        }

        // cache ticket if ok and return
        if ( ! is_null ( $ticket ) ) {
            $USER->edusharing_userticket = $ticket;
            $USER->edusharing_userticketvalidationts = time();
        }

        return $ticket;
    }


    public function encryptWithRepoKey( $data ) {

        $dataEncrypted = '';
        $key = $this->config->getRepoPublicKey();

        $repoPublicKey      = openssl_get_publickey( $key );
        $encryption_status  = openssl_public_encrypt( $data ,$dataEncrypted, $repoPublicKey );

        if( $encryption_status === false || $dataEncrypted === false ) {
            error_log('Encryption error');
            exit();
        }
        return $dataEncrypted;
    }

}
?>
