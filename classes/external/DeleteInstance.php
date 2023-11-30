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

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use Exception;
use mod_edusharing\Constants;
use mod_edusharing\EduSharingService;

class DeleteInstance extends external_api {
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
        $edudeletestructure = new external_single_structure([
            'id'       => new external_value(PARAM_INT, 'id'),
            'courseId' => new external_value(PARAM_INT, 'course id'),
        ]);
        return new external_function_parameters(['eduDeleteStructure' => $edudeletestructure]);
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
            'success' => new external_value(PARAM_BOOL, 'Success?'),
        ]);
    }

    /**
     * Function execute
     *
     * handles the service call
     *
     * @param array $edudeletestructure
     * @return array
     * @throws Exception
     */
    public static function execute(array $edudeletestructure): array {
        global $DB;
        try {
            $context = context_course::instance($edudeletestructure['courseId']);
            require_capability('atto/edusharing:visible', $context);
            $where = [
                'id'     => $edudeletestructure['id'],
                'course' => $edudeletestructure['courseId'],
            ];
            $DB->get_record(Constants::EDUSHARING_TABLE, $where, MUST_EXIST);
            $service = new EduSharingService();
            $service->deleteInstance((string)$edudeletestructure['id']);
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return ['success' => false];
        }
        return ['success' => true];
    }
}
