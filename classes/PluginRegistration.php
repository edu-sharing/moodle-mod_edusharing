<?php declare(strict_types = 1);

namespace mod_edusharing;

use EduSharingApiClient\CurlHandler;
use JsonException;
use mod_edusharing\apiService\MoodleCurlHandler;

class PluginRegistration
{
    private CurlHandler $curlHandler;
    public function __construct() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
        $this->init();
    }

    private function init(): void {
        $this->curlHandler = new MoodleCurlHandler();
    }

    /**
     * Function registerPlugin
     *
     * @throws EduSharingUserException
     * @throws JsonException
     */
    public function registerPlugin(string $repoUrl, string $login, string $pwd, string $data): array {
        $this->validateAlfrescoSession($repoUrl, $login . ':' . $pwd);
        return $this->performRegistration($repoUrl, $data, $login . ':' . $pwd);
    }

    /**
     * @throws EduSharingUserException
     * @throws JsonException
     */
    private function validateAlfrescoSession(string $repoUrl, string $auth): void {
        $headers     = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic '. base64_encode($auth)
        ];
        $url    = $repoUrl . 'rest/authentication/v1/validateSession';
        $result = $this->curlHandler->handleCurlRequest($url, [
            'CURLOPT_RETURNTRANSFER' => 1,
            'CURLOPT_HTTPHEADER'     => $headers
        ]);
        if ($result->error !== 0) {
            throw new EduSharingUserException('API connection error');
        }
        $data = json_decode($result->content, true, 512, JSON_THROW_ON_ERROR);
        if (($data['isAdmin'] ?? false) === false) {
            throw new EduSharingUserException('Given user / password was not accepted as admin');
        }
    }

    /**
     * @throws EduSharingUserException
     * @throws JsonException
     */
    private function performRegistration(string $repoUrl, string $data, string $auth): array {
        $registrationUrl = $repoUrl.'rest/admin/v1/applications/xml';
        $delimiter       = '-------------' . uniqid();
        $body            = $this->getRegistrationApiBody($delimiter, $data);
        $headers         = [
            'Content-Type: multipart/form-data; boundary=' . $delimiter,
            'Content-Length: ' . strlen($body),
            'Accept: application/json',
            'Authorization: Basic '. base64_encode($auth)
        ];
        $this->curlHandler->setMethod(CurlHandler::METHOD_PUT);
        $result = $this->curlHandler->handleCurlRequest($registrationUrl, [
            'CURLOPT_RETURNTRANSFER' => 1,
            'CURLOPT_HTTPHEADER'     => $headers
        ]);
        if ($result->error !== 0) {
            throw new EduSharingUserException('API connection error');
        }

        return json_decode($result->content, true, 512, JSON_THROW_ON_ERROR);
    }

    private function getRegistrationApiBody(string $delimiter, string $data): string {
        $body = '--' . $delimiter . "\r\n";
        $body .= 'Content-Disposition: form-data; name="' . 'xml' . '"';
        $body .= '; filename="metadata.xml"' . "\r\n";
        $body .= 'Content-Type: text/xml' ."\r\n\r\n";
        $body .= $data."\r\n";
        $body .= "--" . $delimiter . "--\r\n";

        return $body;
    }
}