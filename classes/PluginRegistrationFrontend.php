<?php declare(strict_types = 1);

namespace mod_edusharing;

use Exception;

class PluginRegistrationFrontend
{
    public static function registerPlugin(string $repoUrl, string $login, string $pwd): string {
        $return            = '';
        $errorMessage      = '<h3 class="edu_error">ERROR: Could not register the edusharing-moodle-plugin at: '.$repoUrl.'</h3>';
        $service           = new EduSharingService();
        $registrationLogic = new PluginRegistration($service);
        $metadataLogic     = new MetadataLogic($service);
        $data              = $metadataLogic->createXmlMetadata();
        try {
            $result = $registrationLogic->registerPlugin($repoUrl, $login, $pwd, $data);
        } catch (Exception $exception) {
            $return .= $errorMessage . '<p class="edu_error">' . ($exception instanceof EduSharingUserException ? $exception->getMessage() : 'Unexpected error') . '</p>';
            return $return;
        }
        if (isset($result['appid'])) {
            return '<h3 class="edu_success">Successfully registered the edusharing-moodle-plugin at: '. $repoUrl .'</h3>';
        }
        $return .= $errorMessage .  isset($result['message']) ? '<p class="edu_error">'.$result['message'].'</p>' : '';
        $return .= '<h3>Register the Moodle-Plugin in the Repository manually:</h3>';
        $return .= '<p class="edu_metadata"> To register the Moodle-PlugIn manually got to the 
            <a href="'.$repoUrl.'" target="_blank">Repository</a> and open the "APPLICATIONS"-tab of the "Admin-Tools" interface.<br>
            Only the system administrator may use this tool.<br>
            Enter the URL of the Moodle you want to connect. The URL should look like this:  
            „[Moodle-install-directory]/mod/edusharing/metadata.php".<br>
            Click on "CONNECT" to register the LMS. You will be notified with a feedback message and your LMS instance 
            will appear as an entry in the list of registered applications.<br>
            If the automatic registration failed due to a connection issue caused by a proxy-server, you also need to 
            add the proxy-server IP-address as a "host_aliases"-attribute.
            </p>';

        return $return;
    }
}
