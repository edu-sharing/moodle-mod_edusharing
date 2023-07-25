<?php declare(strict_types = 1);

namespace mod_edusharing;

use JsonException;

class PluginRegistration
{
    private EduSharingService $service;
    public function __construct(EduSharingService $service) {
        $this->service = $service;
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
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
        $result = $this->service->validateSession($repoUrl, $auth);
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
        $delimiter = '-------------' . uniqid();
        $body      = $this->getRegistrationApiBody($delimiter, $data);
        $result    = $this->service->registerPlugin($repoUrl, $delimiter, $body, $auth);
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