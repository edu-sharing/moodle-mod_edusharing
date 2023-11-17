<?php declare(strict_types=1);

namespace mod_edusharing\external;

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use Exception;
use mod_edusharing\Constants;
use mod_edusharing\EduSharingService;

class DeleteInstance extends external_api
{
    /**
     * Function execute_parameters
     *
     * defines the structure of the parameters to be provided
     * The end point expects json as follows:
     *
     * {"eduDeleteStructure": {
     *      "id": 12
     *      "courseId": 5,
     *      }
     * }
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        $eduDeleteStructure = new external_single_structure([
            'id'       => new external_value(PARAM_INT, 'id'),
            'courseId' => new external_value(PARAM_INT, 'course id')
        ]);
        return new external_function_parameters(['eduDeleteStructure' => $eduDeleteStructure]);
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
            'success' => new external_value(PARAM_BOOL, 'Success?')
        ]);
    }

    /**
     * Function execute
     *
     * handles the service call
     *
     * @param array $eduDeleteStructure
     * @return array
     * @throws Exception
     */
    public static function execute(array $eduDeleteStructure): array {
        global $DB;
        try {
            $context = context_course::instance($eduDeleteStructure['courseId']);
            require_capability('atto/edusharing:visible', $context);
            $where = [
                'id'     => $eduDeleteStructure['id'],
                'course' => $eduDeleteStructure['courseId']
            ];
            $DB->get_record(Constants::EDUSHARING_TABLE, $where, MUST_EXIST);
            $service = new EduSharingService();
            $service->deleteInstance((string)$eduDeleteStructure['id']);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return ['success' => false];
        }
        return ['success' => true];
    }
}
