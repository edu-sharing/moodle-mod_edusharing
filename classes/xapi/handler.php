<?php
namespace mod_edusharing\xapi;

defined('MOODLE_INTERNAL') || die();

use core_xapi\handler as handler_base;
use core_xapi\local\statement;
use core_xapi\local\state;
use core\event\base as event_base;
use Exception;
use mod_edusharing\event\xapi_statement_received;
use mod_edusharing\grading\Attempt;
use mod_edusharing\grading\Grader;

final class handler extends handler_base {

    public function statement_to_event(statement $statement): ?event_base {
        global $DB;
        $xapiresult = $statement->get_result();
        if (empty($xapiresult)) {
            return null;
        }

        try {
            $validvalues = [
                'http://adlnet.gov/expapi/verbs/answered',
                'http://adlnet.gov/expapi/verbs/completed',
            ];
            $xapiverbid = $statement->get_verb_id();
            if (!in_array($xapiverbid, $validvalues)) {
                return null;
            }

            $xapiobject = $statement->get_activity_id();
            $query = parse_url($xapiobject, PHP_URL_QUERY);
            parse_str($query, $params);
            $contextid = isset($params['id']) ? (int)$params['id'] : null;

            // We ignore subcontent completion. We only grade the main content.
            if (isset($params['subContentId']) || empty($contextid) || !is_numeric($contextid)) {
                return null;
            }
            $context = \context::instance_by_id($contextid);
            if (!$context instanceof \context_module) {
                return null;
            }
            $cm = get_coursemodule_from_id('edusharing', $context->instanceid);
            if (!$cm) {
                return null;
            }

            $user = $statement->get_user();
            $attempt = Attempt::new_attempt($user, $cm);
            if ($attempt === null) {
                return null;
            }
            $attempt->save_statement($statement);
            $edusharing = $DB->get_record('edusharing', ['id' => $cm->instance], '*', MUST_EXIST);
            $grader = new Grader($edusharing,  $cm->idnumber ?? '');
            $grader->update_grades($user->id);
            $minstatement = $statement->minify();
            $params = [
                'other' => $minstatement,
                'context' => $context,
                'objectid' => $cm->instance,
                'userid' => $user->id,
            ];

            return xapi_statement_received::create($params);
        } catch (Exception $e) {
            return null;
        }
    }

    protected function validate_state(state $state): bool {
        // If you want to support xAPI State API, validate the state similarly.
        // Return true only if you accept state read/write for this activity/context.
        return false;
    }
}
