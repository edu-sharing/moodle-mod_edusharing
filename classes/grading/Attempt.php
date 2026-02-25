<?php

namespace mod_edusharing\grading;

use core_xapi\local\statement;
use Exception;
use stdClass;

class Attempt
{
    /** @var stdClass the edusharing_attempts record. */
    private stdClass $record;

    public function __construct(stdClass $record) {
        $this->record = $record;
    }

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
