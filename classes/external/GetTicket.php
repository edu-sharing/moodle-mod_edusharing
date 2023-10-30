<?php declare(strict_types=1);

namespace mod_edusharing\external;

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use Exception;
use mod_edusharing\EduSharingService;
use required_capability_exception;

/**
 * Class GetTicket
 *
 * Service class for the endpoint 'mod_edusharing_get_ticket'.
 */
class GetTicket extends external_api
{
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
    public static function execute_parameters(): external_function_parameters
    {
        $eduTicketStructure = new external_single_structure([
            'courseId' => new external_value(PARAM_INT, 'course id'),
        ]);
        return new external_function_parameters(['eduTicketStructure' => $eduTicketStructure]);
    }

    /**
     * Function execute_returns
     *
     * defines the return data
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure
    {
        return new external_single_structure([
            'ticket' => new external_value(PARAM_TEXT, 'the ticket'),
        ]);
    }

    /**
     * Function execute
     *
     * handles the service call
     *
     * @throws required_capability_exception
     * @throws Exception
     */
    public static function execute(array $input): array
    {
        if ($input['courseId'] !== 0) {
            $context = context_course::instance($input['courseId']);
            require_capability('moodle/course:update', $context);
        }
        $service = new EduSharingService();
        $ticket  = $service->getTicket();
        return ['ticket' => $ticket];
    }
}
