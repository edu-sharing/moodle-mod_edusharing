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

require_once(dirname(__FILE__, 3) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

global $CFG, $PAGE, $DB;

try {
    $id = optional_param('id', 0, PARAM_INT); // course_module ID, or
    $n  = optional_param('n', 0, PARAM_INT);  // edusharing instance ID - it should be named as the first character of the module
    if ($id !== 0) {
        $cm         = get_coursemodule_from_id('edusharing', $id, 0, false, MUST_EXIST);
        $course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $edusharing = $DB->get_record(Constants::EDUSHARING_TABLE, ['id' => $cm->instance], '*', MUST_EXIST);
        $vid        = $id;
        $courseId   = $course->id;
    } else if ($n !== 0) {
        $edusharing = $DB->get_record(Constants::EDUSHARING_TABLE, ['id' => $n], '*', MUST_EXIST);
        $course     = $DB->get_record('course', ['id' => $edusharing->course], '*', MUST_EXIST);
        $cm         = get_coursemodule_from_instance('edusharing', $edusharing->id, $course->id, false, MUST_EXIST);
        $vid        = $edusharing->id;
        $courseId   = $course->id;
    } else {
        trigger_error(get_string('error_detect_course', 'edusharing'), E_USER_WARNING);
        exit();
    }
    $PAGE->set_url('/mod/edusharing/view.php?id=' . $vid);
    require_login($course, true, $cm);
    try {
        $eduSharingService = new EduSharingService();
        $ticket            = $eduSharingService->getTicket();
    } catch (Exception $exception) {
        trigger_error($exception->getMessage(), E_USER_WARNING);
        exit();
    }
    $utils       = new UtilityFunctions();
    $redirectUrl = $utils->getRedirectUrl($edusharing);
    $ts          = round(microtime(true) * 1000);
    $redirectUrl .= '&ts=' . $ts;
    $data        = get_config('edusharing', 'application_appid') . $ts . $utils->getObjectIdFromUrl($edusharing->object_url);
    $baseHelper  = new EduSharingHelperBase(get_config('edusharing', 'application_cc_gui_url'), get_config('edusharing', 'application_private_key'), get_config('edusharing', 'application_appid'));
    $redirectUrl .= '&sig=' . urlencode($baseHelper->sign($data));
    $redirectUrl .= '&signed=' . urlencode($data);
    $backAction  = '&closeOnBack=true';
    if (empty($edusharing->popup_window)) {
        $backAction = '&backLink=' . urlencode($CFG->wwwroot . '/course/view.php?id=' . $courseId);
    }
    if (!empty($_SERVER['HTTP_REFERER']) && str_contains($_SERVER['HTTP_REFERER'], 'modedit.php')) {
        $backAction = '&backLink=' . urlencode($_SERVER['HTTP_REFERER']);
    }
    $redirectUrl .= $backAction;
    $redirectUrl .= '&ticket=' . urlencode(base64_encode($utils->encryptWithRepoKey($ticket)));
    redirect($redirectUrl);
} catch (Exception $exception) {
    error_log($exception->getMessage());
}
