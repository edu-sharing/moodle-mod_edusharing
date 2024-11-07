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

namespace mod_edusharing\external;

defined('MOODLE_INTERNAL') || die();

// Once Moodle versions < 4.2 are out of LTS, we need to revert this to the proper namespaces.
global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

use context_course;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_edusharing\EduSharingService;
use required_capability_exception;

/**
 * Class GetTicket
 *
 * Service class for the endpoint 'mod_edusharing_get_ticket'.
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class GetTicket extends external_api {
    /**
     * Function execute_parameters
     *
     * defines the structure of the parameters to be provided
     * The end point expects json as follows:
     *
     * {"eduTicketStructure": {
     *      "courseId": 5
     *      }
     * }
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        $eduticketstructure = new external_single_structure([
            'courseId' => new external_value(PARAM_INT, 'course id'),
        ]);
        return new external_function_parameters(['eduTicketStructure' => $eduticketstructure]);
    }

    /**
     * Function execute_returns
     *
     * defines the return data
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ticket' => new external_value(PARAM_TEXT, 'the ticket'),
        ]);
    }

    /**
     * Function execute
     *
     * handles the service call
     *
     * @param array $input
     * @return array
     * @throws required_capability_exception
     */
    public static function execute(array $input): array {
        if ($input['courseId'] !== 0) {
            $context = context_course::instance($input['courseId']);
            require_capability('moodle/course:update', $context);
        }
        $service = new EduSharingService();
        $ticket  = $service->get_ticket();
        return ['ticket' => $ticket];
    }
}
