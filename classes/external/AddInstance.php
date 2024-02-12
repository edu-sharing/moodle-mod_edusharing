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
use mod_edusharing\EduSharingService;
use stdClass;

/**
 * class AddInstance
 *
 * Service class for the endpoint 'mod_edusharing_add_instance'.
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 */
class AddInstance extends external_api {
    /**
     * Function execute_parameters
     *
     * defines the structure of the parameters to be provided
     * The end point expects json as follows:
     *
     * {"eduStructure": {
     *      "name": "testName",
     *      "objectUrl": "www.test.de",
     *      "courseId": 5,
     *      "objectVersion": 1.1
     *      }
     * }
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        $edustructure = new external_single_structure([
            'name'          => new external_value(PARAM_TEXT, 'the name of the object'),
            'objectUrl'     => new external_value(PARAM_TEXT, 'the object url of the object'),
            'courseId'      => new external_value(PARAM_INT, 'course id'),
            'objectVersion' => new external_value(PARAM_TEXT, 'The object version'),
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
            'name'          => new external_value(PARAM_TEXT, 'the name of the object'),
            'objectUrl'     => new external_value(PARAM_TEXT, 'the object url of the object'),
            'courseId'      => new external_value(PARAM_INT, 'course id'),
            'id'            => new external_value(PARAM_INT, 'id'),
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
        $context = context_course::instance($edustructure['courseId']);
        require_capability('atto/edusharing:visible', $context);
        $edusharing                 = new stdClass();
        $edusharing->name           = $edustructure['name'];
        $edusharing->object_url     = $edustructure['objectUrl'];
        $edusharing->course         = $edustructure['courseId'];
        $edusharing->object_version = $edustructure['objectVersion'];
        $edusharing->introformat    = 0;
        $service                    = new EduSharingService();
        $id                         = $service->add_instance($edusharing);
        if ($id === false) {
            throw new Exception('Error adding instance');
        }
        $edustructure['id'] = $id;
        return $edustructure;
    }
}
