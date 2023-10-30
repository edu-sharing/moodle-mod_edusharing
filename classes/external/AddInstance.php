<?php declare(strict_types=1);

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
 */
class AddInstance extends external_api
{
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
    public static function execute_parameters(): external_function_parameters
    {
        $eduStructure = new external_single_structure([
            'name'          => new external_value(PARAM_TEXT, 'the name of the object'),
            'objectUrl'     => new external_value(PARAM_TEXT, 'the object url of the object'),
            'courseId'      => new external_value(PARAM_INT, 'course id'),
            'objectVersion' => new external_value(PARAM_TEXT, 'The object version')
        ]);
        return new external_function_parameters(['eduStructure' => $eduStructure]);
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
            'name'          => new external_value(PARAM_TEXT, 'the name of the object'),
            'objectUrl'     => new external_value(PARAM_TEXT, 'the object url of the object'),
            'courseId'      => new external_value(PARAM_INT, 'course id'),
            'id'            => new external_value(PARAM_INT, 'id'),
            'objectVersion' => new external_value(PARAM_TEXT, 'The object version')
        ]);
    }

    /**
     * Function execute
     *
     * handles the service call
     *
     * @param array $eduStructure
     * @return array
     * @throws Exception
     */
    public static function execute(array $eduStructure): array
    {
        error_log('called with:' . json_encode($eduStructure));
        $context = context_course::instance($eduStructure['courseId']);
        require_capability('atto/edusharing:visible', $context);
        $eduSharing                 = new stdClass();
        $eduSharing->name           = $eduStructure['name'];
        $eduSharing->object_url     = $eduStructure['objectUrl'];
        $eduSharing->course         = $eduStructure['courseId'];
        $eduSharing->object_version = $eduStructure['objectVersion'];
        $eduSharing->introformat    = 0;
        $service                    = new EduSharingService();
        $id                         = $service->addInstance($eduSharing);
        if ($id === false) {
            throw new Exception('Error adding instance');
        }
        $eduStructure['id'] = $id;
        return $eduStructure;
    }
}
