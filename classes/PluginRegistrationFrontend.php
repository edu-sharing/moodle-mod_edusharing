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

declare(strict_types = 1);

namespace mod_edusharing;

use Exception;

/**
 * Class PluginRegistrationFrontend
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package    mod_edusharing
 */
class PluginRegistrationFrontend {
    /**
     * Function register_plugin
     *
     * @param string $repourl
     * @param string $login
     * @param string $pwd
     * @return string
     */
    public static function register_plugin(string $repourl, string $login, string $pwd): string {
        $return            = '';
        $errormessage      = '<h3 class="edu_error">ERROR: Could not register the edusharing-moodle-plugin at: '.$repourl.'</h3>';
        $service           = new EduSharingService();
        $registrationlogic = new PluginRegistration($service);
        $metadatalogic     = new MetadataLogic($service);
        $data              = $metadatalogic->create_xml_metadata();
        try {
            $result = $registrationlogic->register_plugin($repourl, $login, $pwd, $data);
        } catch (Exception $exception) {
            $exceptionmessage = $exception instanceof EduSharingUserException
                ? $exception->getMessage() : 'Unexpected error';
            $return .= $errormessage . '<p class="edu_error">' . $exceptionmessage . '</p>';
            return $return;
        }
        if (isset($result['appid'])) {
            return '<h3 class="edu_success">Successfully registered the edusharing-moodle-plugin at: '. $repourl .'</h3>';
        }
        $return .= $errormessage .  isset($result['message']) ? '<p class="edu_error">'.$result['message'].'</p>' : '';
        $return .= '<h3>Register the Moodle-Plugin in the Repository manually:</h3>';
        // phpcs:disable -- just messy html.
        $return .= '<p class="edu_metadata"> To register the Moodle-PlugIn manually got to the
            <a href="'.$repourl.'" target="_blank"> Repository</a> and open the "APPLICATIONS"-tab of the "Admin-Tools" interface.<br>
            Only the system administrator may use this tool.<br>
            Enter the URL of the Moodle you want to connect. The URL should look like this:  
            „[Moodle-install-directory]/mod/edusharing/metadata.php".<br>
            Click on "CONNECT" to register the LMS. You will be notified with a feedback message and your LMS instance 
            will appear as an entry in the list of registered applications.<br>
            If the automatic registration failed due to a connection issue caused by a proxy-server, you also need to 
            add the proxy-server IP-address as a "host_aliases"-attribute.
            </p>';
        // phpcs:enable

        return $return;
    }
}
