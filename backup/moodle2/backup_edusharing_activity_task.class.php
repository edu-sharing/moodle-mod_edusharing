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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/edusharing/backup/moodle2/backup_edusharing_stepslib.php');
require_once($CFG->dirroot . '/mod/edusharing/backup/moodle2/backup_edusharing_settingslib.php');

/**
 * Class backup_edusharing_activity_task
 *
 * choice backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_edusharing_activity_task extends backup_activity_task {
    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step.
        $this->add_step(new backup_edusharing_activity_structure_step('edusharing_structure', 'edusharing.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     * @param string $content
     * @return array|string|string[]|null
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // phpcs:disable
        // Link to the list of edusharing.
        $search = "/(".$base."\/mod\/edusharing\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@EDUSHARINGINDEX*$2@$', $content);

        // Link to edusharing view by moduleid.
        $search = "/(".$base."\/mod\/edusharing\/view.php\?id\=)([0-9]+)/";
        return preg_replace($search, '$@EDUSHARINGVIEWBYID*$2@$', $content);
        // phpcs:enable
    }
}
