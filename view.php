<?php
// This file is part of Moodle - http://moodle.org/
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints a particular instance of edusharing
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use EduSharingApiClient\EduSharingHelperBase;
use mod_edusharing\Constants;
use mod_edusharing\EduSharingService;
use mod_edusharing\UtilityFunctions;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

global $CFG, $PAGE, $DB;

try {
    $id = optional_param('id', 0, PARAM_INT); // Course_module ID or.
    $n  = optional_param('n', 0, PARAM_INT);  // Edusharing instance ID - it should be named as the first character of the module.
    if ($id !== 0) {
        $cm         = get_coursemodule_from_id('edusharing', $id, 0, false, MUST_EXIST);
        $course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $edusharing = $DB->get_record(Constants::EDUSHARING_TABLE, ['id' => $cm->instance], '*', MUST_EXIST);
        $vid        = $id;
        $courseid   = $course->id;
    } else if ($n !== 0) {
        $edusharing = $DB->get_record(Constants::EDUSHARING_TABLE, ['id' => $n], '*', MUST_EXIST);
        $course     = $DB->get_record('course', ['id' => $edusharing->course], '*', MUST_EXIST);
        $cm         = get_coursemodule_from_instance('edusharing', $edusharing->id, $course->id, false, MUST_EXIST);
        $vid        = $edusharing->id;
        $courseid   = $course->id;
    } else {
        trigger_error(get_string('error_detect_course', 'edusharing'), E_USER_WARNING);
        exit();
    }
    $PAGE->set_url('/mod/edusharing/view.php?id=' . $vid);
    require_login($course, true, $cm);
    try {
        $edusharingservice = new EduSharingService();
        $ticket            = $edusharingservice->get_ticket();
    } catch (Exception $exception) {
        trigger_error($exception->getMessage(), E_USER_WARNING);
        exit();
    }
    $utils       = new UtilityFunctions();
    $redirecturl = $utils->get_redirect_url($edusharing);
    $ts          = round(microtime(true) * 1000);
    $redirecturl .= '&ts=' . $ts;
    $data        = get_config('edusharing', 'application_appid') . $ts . $utils->get_object_id_from_url($edusharing->object_url);
    $basehelper  = new EduSharingHelperBase(
        baseUrl: get_config('edusharing', 'application_cc_gui_url'),
        privateKey: get_config('edusharing', 'application_private_key'),
        appId: get_config('edusharing', 'application_appid')
    );
    $redirecturl .= '&sig=' . urlencode($basehelper->sign($data));
    $redirecturl .= '&signed=' . urlencode($data);
    $backaction  = '&closeOnBack=true';
    if (empty($edusharing->popup_window)) {
        $backaction = '&backLink=' . urlencode($CFG->wwwroot . '/course/view.php?id=' . $courseid);
    }
    if (!empty($_SERVER['HTTP_REFERER']) && str_contains($_SERVER['HTTP_REFERER'], 'modedit.php')) {
        $backaction = '&backLink=' . urlencode($_SERVER['HTTP_REFERER']);
    }
    $redirecturl .= $backaction;
    $redirecturl .= '&ticket=' . urlencode(base64_encode($utils->encrypt_with_repo_key($ticket)));
    redirect($redirecturl);
} catch (Exception $exception) {
    debugging($exception->getMessage());
}
