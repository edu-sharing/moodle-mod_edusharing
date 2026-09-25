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

namespace mod_edusharing\local;

use backup;
use core_backup\hook\after_restore_root_define_settings;
use html_writer;
use mod_edusharing\RestoreRightsChecker;
use Throwable;

/**
 * Class hook_callbacks
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Function after_restore_root_define_settings
     *
     * Runs while the restore is prepared, before any setting is shown. Warns the user about
     * edu-sharing objects in the backup they lack publish rights for, as these will be stripped
     * from the course: activities by restore_edusharing_activity_task, embedded objects by
     * RestoreHelper::convert_object
     *
     * @param after_restore_root_define_settings $hook
     * @return void
     */
    public static function after_restore_root_define_settings(after_restore_root_define_settings $hook): void {
        $task = $hook->task;
        try {
            $checker = new RestoreRightsChecker();
            $objects = $checker->collect_backup_objects($task->get_basepath(), $task->get_info());
            if (empty($objects)) {
                return;
            }
            $denied = $checker->get_denied_objects($objects, (int)$task->get_userid());
        } catch (Throwable $exception) {
            // Never keep the restore from being set up, the objects are checked again when restored.
            debugging('Checking edu-sharing publish rights ahead of the restore failed: ' . $exception->getMessage());
            return;
        }
        if (empty($denied)) {
            return;
        }
        $items = array_map(
            fn(array $object): string => get_string('restore_missing_rights_' . $object['kind'], 'edusharing',
                s($object['title'] !== '' ? $object['title'] : $object['nodeid'])),
            $denied
        );
        $task->log(
            get_string('restore_missing_rights_log', 'edusharing', implode(', ', $items)),
            backup::LOG_WARNING
        );
        if (!CLI_SCRIPT && !AJAX_SCRIPT) {
            \core\notification::warning(
                get_string('restore_missing_rights_warning', 'edusharing', html_writer::alist($items))
            );
        }
    }
}
