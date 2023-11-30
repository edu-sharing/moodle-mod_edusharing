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

class UpdateInstance extends external_api {
    /**
     * Function execute_parameters
     *
     * defines the structure of the parameters to be provided
     * The end point expects json as follows:
     *
     * {"eduStructure": {
     *      "id": 1234,
     *      "courseId": 123
     *      }
     * }
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        $edustructure = new external_single_structure([
            'id'            => new external_value(PARAM_INT, 'the primary id of the pbject'),
            'name'          => new external_value(PARAM_TEXT, 'the name of the object', VALUE_OPTIONAL),
            'objectUrl'     => new external_value(PARAM_TEXT, 'the object url of the object', VALUE_OPTIONAL),
            'courseId'      => new external_value(PARAM_INT, 'course id'),
            'objectVersion' => new external_value(PARAM_TEXT, 'The object version', VALUE_OPTIONAL),
        ]);
        return new external_function_parameters(['eduStructure' => $edustructure]);
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
            'id'            => new external_value(PARAM_INT, 'id'),
            'name'          => new external_value(PARAM_TEXT, 'the name of the object'),
            'objectUrl'     => new external_value(PARAM_TEXT, 'the object url of the object'),
            'courseId'      => new external_value(PARAM_INT, 'course id'),
            'objectVersion' => new external_value(PARAM_TEXT, 'The object version'),
        ]);
    }

    /**
     * Function execute
     *
     * handles the service call
     *
     * @param array $edustructure
     * @return array
     * @throws Exception
     */
    public static function execute(array $edustructure): array {
        global $DB;
        $context = context_course::instance($edustructure['courseId']);
        require_capability('atto/edusharing:visible', $context);
        $where          = [
            'id'     => $edustructure['id'],
            'course' => $edustructure['courseId'],
        ];
        $eduinstance    = $DB->get_record(Constants::EDUSHARING_TABLE, $where, MUST_EXIST);
        $isupdateneeded = false;
        if (isset($edustructure['name']) && $edustructure['name'] !== $eduinstance->name) {
            $eduinstance->name = $edustructure['name'];
            $isupdateneeded    = true;
        }
        if (isset($edustructure['objectUrl']) && $edustructure['objectUrl'] !== $eduinstance->object_url) {
            $eduinstance->object_urk = $edustructure['objectUrl'];
            $isupdateneeded          = true;
        }
        if (isset($edustructure['objectVersion']) && $edustructure['objectVersion'] !== $eduinstance->object_version) {
            $eduinstance->object_version = $edustructure['objectVersion'];
            $isupdateneeded              = true;
        }
        if ($isupdateneeded) {
            $eduinstance->timemodified = time();
            $DB->update_record(Constants::EDUSHARING_TABLE, $eduinstance);
        }
        return [
            'name'          => $eduinstance->name,
            'objectUrl'     => $eduinstance->object_url,
            'courseId'      => $eduinstance->course,
            'id'            => $eduinstance->id,
            'objectVersion' => $eduinstance->object_version,
        ];
    }
}
