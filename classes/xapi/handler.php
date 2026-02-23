<?php
namespace mod_edusharing\xapi;

defined('MOODLE_INTERNAL') || die();

use core_xapi\handler as handler_base;
use core_xapi\local\statement;
use core_xapi\local\state;
use core\event\base as event_base;
use mod_edusharing\event\xapi_statement_received;

final class handler extends handler_base {

    public function statement_to_event(statement $statement): ?event_base {
        $xapiresult = $statement->get_result();
        if (empty($xapiresult)) {
            return null;
        }

        $validvalues = [
            'http://adlnet.gov/expapi/verbs/answered',
            'http://adlnet.gov/expapi/verbs/completed',
        ];
        $xapiverbid = $statement->get_verb_id();
        if (!in_array($xapiverbid, $validvalues)) {
            return null;
        }

        // 3) Convert to a Moodle event (must be a real \core\event\base).
        $params = [
            'context' => \context_system::instance(), // Prefer module context if you can derive it.
            'other' => $statement->minify(),
        ];

        return xapi_statement_received::create($params);
    }

    protected function validate_state(state $state): bool {
        // If you want to support xAPI State API, validate the state similarly.
        // Return true only if you accept state read/write for this activity/context.
        return false;
    }
}
