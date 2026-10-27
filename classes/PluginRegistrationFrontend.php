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

use core\output\notification;
use Exception;
use html_writer;
use moodle_url;

/**
 * Class PluginRegistrationFrontend
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
        global $OUTPUT;
        $return            = '';

        $service           = new EduSharingService();
        $registrationlogic = new PluginRegistration($service);
        $metadatalogic     = new MetadataLogic($service);
        $data              = $metadatalogic->create_xml_metadata();
        $saferepourl       = clean_param($repourl, PARAM_URL);
        $saferepourltext   = s($saferepourl);
        $errormessage      = get_string('registration_error', 'edusharing', $saferepourltext);
        $backbutton        = html_writer::link(
            new moodle_url('/admin/settings.php', ['section' => 'modsettingedusharing']),
            get_string('back'),
            ['class' => 'btn btn-primary']
        );
        try {
            $result = $registrationlogic->register_plugin($repourl, $login, $pwd, $data);
        } catch (Exception $exception) {
            $exceptionmessage = $exception instanceof EduSharingUserException
                ? $exception->getMessage() : get_string('import_metadata_unexpected_error', 'edusharing');
            $return .= $OUTPUT->notification($errormessage . '<br>' . s($exceptionmessage), notification::NOTIFY_ERROR);
            return $return . $backbutton;
        }
        if (isset($result['id']) || isset($result['appid'])) {
            return $OUTPUT->notification(
                get_string('registration_success', 'edusharing', $saferepourltext),
                notification::NOTIFY_SUCCESS
            ) . $backbutton;
        }
        $return .= $OUTPUT->notification(
            $errormessage . (isset($result['message']) ? '<br>' . s($result['message']) : ''),
            notification::NOTIFY_ERROR
        );
        $return .= $OUTPUT->heading(get_string('registration_manual_heading', 'edusharing'), 3);
        $return .= html_writer::tag('p', get_string('registration_manual_help', 'edusharing', $saferepourltext));
        return $return . $backbutton;
    }
}
