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

use core_external\external_api;
use Exception;
use external_single_structure;
use core_external\external_value;
use external_function_parameters;
use mod_edusharing\EduSharingService;

defined('MOODLE_INTERNAL') || die();

// Once Moodle versions < 4.2 are out of LTS, we need to revert this to the proper namespaces.
global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * class GetSecuredNode
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class GetSecuredNode extends external_api {

    /**
     * Function execute_parameters
     *
     * defines the structure of the parameters to be provided
     * The end point expects json as follows:
     *
     * {"eduSecuredNodeStructure": {
     *      "nodeId": "string"
     *      }
     * }
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        $structure = new external_single_structure([
            'nodeId' => new external_value(PARAM_TEXT, 'node id'),
            'resourceId' => new external_value(PARAM_TEXT, 'resource id'),
            'version' => new external_value(PARAM_TEXT, 'version', VALUE_DEFAULT, '-1'),
        ]);
        return new external_function_parameters(['eduSecuredNodeStructure' => $structure]);
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
            'securedNode' => new external_value(PARAM_TEXT, 'the secured node'),
            'signature' => new external_value(PARAM_TEXT, 'the signature to verify the nodes integrity'),
            'jwt' => new external_value(PARAM_TEXT, 'the jwt for the rendering service 2'),
            'renderingBaseUrl' => new external_value(PARAM_TEXT, 'the rendering 2 base url'),
            'previewUrl' => new external_value(PARAM_TEXT, 'the preview url'),
            'customWidth' => new external_value(PARAM_TEXT, 'the custom width'),
        ]);
    }

    /**
     * Function execute
     *
     * handles the service call
     *
     * @param array $structure
     * @return array
     * @throws Exception
     */
    public static function execute(array $structure): array {
        $service = new EduSharingService();
        $securednode = $service->get_secured_node($structure['nodeId'], $structure['resourceId'], $structure['version']);
        $renderingurl = $service->get_rendering_2_url();
        $test = $service->get_custom_width($securednode->node);
        return [
            'securedNode' => $securednode->securedNode,
            'signature' => $securednode->signature,
            'jwt' => $securednode->jwt,
            'renderingBaseUrl' => $renderingurl,
            'previewUrl' => $securednode->previewUrl,
            'customWidth' => $test,
        ];
    }
}
