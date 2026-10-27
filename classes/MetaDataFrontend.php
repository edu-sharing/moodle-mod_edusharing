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

/**
 * Class MetaDataFrontend
 *
 * @author Marian Ziegler <ziegler@edu-sharnig.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MetaDataFrontend {
    /**
     * Function get_repo_form
     *
     * @return string|null
     */
    public static function get_repo_form(): ?string {
        global $OUTPUT;
        try {
            $repourl     = get_config('edusharing', 'application_cc_gui_url') !== false
                ? get_config('edusharing', 'application_cc_gui_url') : '';
            $appid       = get_config('edusharing', 'application_appid') !== false
                ? get_config('edusharing', 'application_appid') : '';
            $hostaliases = get_config('edusharing', 'application_host_aliases') !== false
                ? get_config('edusharing', 'application_host_aliases') : '';
        } catch (Exception $exception) {
            unset($exception);
            return $OUTPUT->notification(get_string('import_metadata_unexpected_error', 'edusharing'), notification::NOTIFY_ERROR);
        }
        if (empty($repourl)) {
            return null;
        }
        return $OUTPUT->render_from_template('mod_edusharing/registration_form', [
            'sesskey'     => sesskey(),
            'repourl'     => $repourl,
            'appid'       => $appid,
            'hostaliases' => $hostaliases,
        ]);
    }

    /**
     * Function get_meta_data_form
     *
     * Returns the form to input the metadata url
     *
     * @return string
     */
    public static function get_meta_data_form(): string {
        global $CFG, $OUTPUT;
        return $OUTPUT->render_from_template('mod_edusharing/import_metadata_form', [
            'sesskey'   => sesskey(),
            'example'   => 'http://your-server-name/edu-sharing/metadata?format=lms&external=true',
            'exporturl' => $CFG->wwwroot . '/mod/edusharing/metadata.php',
        ]);
    }
}
