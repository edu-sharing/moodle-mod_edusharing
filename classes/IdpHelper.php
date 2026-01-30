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

use Exception;

/**
 * Class IdpHelper
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class IdpHelper
{
    /**
     * @var UtilityFunctions
     */
    private UtilityFunctions $utils;

    /**
     * IdpHelper constructor
     */
    public function __construct() {
        $this->utils = new UtilityFunctions();
    }

    /**
     * Checks if the current user has access to educational resources.
     *
     * This method determines the access by verifying a specific configuration setting,
     * checking user custom profile fields, and confirming membership in a specified cohort.
     *
     * @return bool Returns true if the user has educational access based on either their profile
     *              or cohort membership; otherwise, false.
     */
    public function check_edu_access(): bool {
        global $USER, $DB, $CFG;
        try {
            $idpoptionon = $this->utils->get_config_entry('use_as_idp') === '1';
        } catch (Exception) {
            return true;
        }
        if (!$idpoptionon) {
            return true;
        }
        require_once($CFG->dirroot . '/user/profile/lib.php');
        profile_load_custom_fields($USER);
        $profilefieldset = (isset($USER->profile['eduAccess']) && $USER->profile['eduAccess'] == 1);
        try {
            $cohort = $DB->get_record('cohort', ['idnumber' => 'edu_access'], '*', MUST_EXIST);
            $userisincohort = $DB->record_exists('cohort_members', [
                'cohortid' => $cohort->id,
                'userid' => $USER->id,
            ]);
        } catch (Exception) {
            $userisincohort = false;
        }
        return $userisincohort || $profilefieldset;
    }
}
