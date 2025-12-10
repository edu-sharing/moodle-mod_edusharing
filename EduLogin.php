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
 * Redirect to login page setting session parameter.
 *
 * @package mod_edusharing
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.MoodleInternal.MoodleInternalGlobalState
// phpcs:disable moodle.Files.RequireLogin.Missing

require_once(dirname(__FILE__) . '/../../config.php');

global $SESSION, $CFG, $USER, $DB;

require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

use mod_edusharing\EduSharingService;
use mod_edusharing\IdpHelper;
use mod_edusharing\UtilityFunctions;

try {
    $utils = new UtilityFunctions();
    $idphelper = new IdpHelper();
    if ($utils->get_config_entry('use_as_idp') !== '1') {
        redirect(new moodle_url('/login/index.php'));
    }
    if (isloggedin()) {
        if (! $idphelper->check_edu_access()) {
            redirect(new moodle_url('/login/index.php'));
        }
        $service = new EduSharingService();
        $ticket  = $service->get_ticket();
        $repourl = rtrim($utils->get_config_entry('application_cc_gui_url'), '/') . '/components/login?ticket=' . $ticket;
        redirect(new moodle_url($repourl));
    }
    $SESSION->redirect_to_edusharing = true;
    redirect(new moodle_url('/login/index.php'));
} catch (Exception $exception) {
    mtrace('Error: ' . $exception->getMessage());
}
