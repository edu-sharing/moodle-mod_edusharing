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
 * Helpers for the upgrade steps of mod_edusharing (db/upgrade.php)
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_edusharing\task\regrade_attempts;

/**
 * Repairs edusharing_attempts.scaled where it has no decimals.
 *
 * The upgrade step 2026031603 created the table with add_field('scaled', XMLDB_TYPE_NUMBER, '10', '5', ...):
 * the decimals belong into the precision ('10, 5'), the fourth argument is the obsolete $unsigned, so
 * the field became NUMBER(10, 0). Sites that got the table that way, rather than from install.xml,
 * stored every H5P result between 0 and 1 rounded to 0 or 1, and graded with it.
 *
 * This gives the field its five decimals, computes the scaled scores again from the raw and the
 * maximum score they come from (grading\Attempt), and queues the regrade of the activities graded
 * from attempts (task\regrade_attempts): Moodle writes no grades during an upgrade. Scores are
 * found by their values, not by the field, so a repair interrupted after the field was changed
 * is completed by the next run.
 *
 * @return bool true if the field or scores had to be repaired, false if they were correct already
 */
function mod_edusharing_repair_attempt_scores(): bool {
    global $DB;

    $dbmanager = $DB->get_manager();
    $table     = new xmldb_table('edusharing_attempts');
    if (!$dbmanager->table_exists($table)) {
        return false;
    }
    $columns     = $DB->get_columns('edusharing_attempts', false);
    $fieldbroken = isset($columns['scaled'])
        && ((int)$columns['scaled']->scale !== 5 || (int)$columns['scaled']->max_length !== 10);
    if ($fieldbroken) {
        // By way of another length: Moodle's PostgreSQL generator takes a scale of 0 for no
        // decimals and alters nothing for a change of the decimals alone. A length left at 11
        // by an interrupted run counts as broken, so the next run puts it back.
        foreach (['11, 5', '10, 5'] as $precision) {
            $field = new xmldb_field('scaled', XMLDB_TYPE_NUMBER, $precision, null, XMLDB_NOTNULL, null, '0', 'maxscore');
            $dbmanager->change_field_precision($table, $field);
        }
    }

    // Attempt stores scaled = rawscore / maxscore for a result with a maximum, and 0 otherwise.
    $wrongscores = $DB->count_records_select(
        'edusharing_attempts',
        'maxscore <> 0 AND ABS(scaled - rawscore * 1.0 / maxscore) > 0.00001'
    );
    if ($wrongscores > 0) {
        $DB->execute('UPDATE {edusharing_attempts} SET scaled = rawscore * 1.0 / maxscore WHERE maxscore <> 0');
        \core\task\manager::queue_adhoc_task(new regrade_attempts(), true);
    }
    return $fieldbroken || $wrongscores > 0;
}
