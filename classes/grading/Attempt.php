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

namespace mod_edusharing\grading;

use core_xapi\local\statement;
use Exception;
use stdClass;

/**
 * class Attempt
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Attempt
{
    /** @var stdClass the edusharing_attempts record. */
    private stdClass $record;

    /**
     * Function __construct
     *
     * @param stdClass $record
     */
    public function __construct(stdClass $record) {
        $this->record = $record;
    }

    /**
     * Function new_attempt
     *
     * @param stdClass $user
     * @param stdClass $cm
     * @return Attempt|null
     */
    public static function new_attempt(stdClass $user, stdClass $cm): ?Attempt {
        global $DB;
        $record = new stdClass();
        $record->edusharingid = $cm->instance;
        $record->userid = $user->id;
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;
        $record->rawscore = 0;
        $record->maxscore = 0;
        $record->duration = 0;
        $record->completion = null;
        $record->success = null;
        try {
            $conditions = ['edusharingid' => $cm->instance, 'userid' => $user->id];
            $attemptscount = $DB->count_records('edusharing_attempts', $conditions);
            $record->attempt = $attemptscount + 1;
            $record->id = $DB->insert_record('edusharing_attempts', $record);
            if (!$record->id) {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }

        return new Attempt($record);
    }

    /**
     * Function save_statement
     *
     * @param statement $statement
     * @return void
     * @throws Exception
     */
    public function save_statement(statement $statement): void {
        global $DB;
        $xapiobject = $statement->get_object();
        $xapiresult = $statement->get_result();
        if (empty($xapiobject) || empty($xapiresult)) {
            throw new Exception('No xAPI object and/or result found in statement');
        }
        $xapidefinition = $xapiobject->get_definition();
        if (empty($xapidefinition)) {
            throw new Exception('No xAPI definition found in statement');
        }
        $result = $xapiresult->get_data();
        $this->record->duration = $xapiresult->get_duration();
        if (isset($result->completion)) {
            $this->record->completion = ($result->completion) ? 1 : 0;
        } else {
            $this->record->completion = null;
        }
        if (isset($result->success)) {
            $this->record->success = ($result->success) ? 1 : 0;
        } else {
            $this->record->success = null;
        }
        if (isset($result->score)) {
            $maxscore = $result->score->max ?? 0;
            if ($maxscore !== 0) {
                $this->record->rawscore = $result->score->raw ?? 0;
                $this->record->maxscore = $maxscore;
                $this->record->scaled = $this->record->rawscore / $this->record->maxscore;
            }
        }
        if (!$DB->update_record('edusharing_attempts', $this->record)) {
            throw new Exception('Failed to update attempt record');
        }
    }
}
