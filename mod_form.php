<?php
// This file is part of Moodle - http://moodle.org/
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The main edusharing configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use EduSharingApiClient\Usage;
use mod_edusharing\EduSharingService;
use mod_edusharing\grading\Grader;
use mod_edusharing\Constants;
use mod_edusharing\UtilityFunctions;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * The main edusharing configuration form
 *
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_edusharing_mod_form extends moodleform_mod {
    /**
     * (non-PHPdoc)
     * @see lib/moodleform::definition()
     */
    public function definition(): void {


        global $PAGE;
        try {
            $service = new EduSharingService();
            $utils = new UtilityFunctions();
            $hasgradingfeature = $service->has_rendering_2();
            $currentedusharing = $this->current;
            $mediatype = '';
            if (!empty($currentedusharing)) {
                $usage = new Usage(
                    nodeId: $utils->get_object_id_from_url($currentedusharing->object_url),
                    nodeVersion: $currentedusharing->object_version,
                    containerId: $currentedusharing->course,
                    resourceId: $currentedusharing->id,
                    usageId: $currentedusharing->usage_id
                );
                $node = $service->get_node(usage: $usage, rendering2: true);
                $mediatype = $node['node']['mediatype'] ?? '';
            }
            $repobase = $utils->get_config_entry('application_cc_gui_url');
            $PAGE->requires->js_call_amd('mod_edusharing/modform', 'init', [trim($repobase, '/'), $mediatype, $hasgradingfeature]);
            // Adding the "general" fieldset, where all the common settings are shown.
            $this->_form->addElement('header', 'general', get_string('general', 'form'));
            // Adding the standard "name" field.
            $this->_form->addElement(
                'text',
                'name',
                get_string('edusharingname', Constants::EDUSHARING_MODULE_NAME),
                ['size' => '64']
            );
            $this->_form->setType('name', PARAM_TEXT);
            $this->_form->addRule('name', null, 'required', null, 'client');
            $this->_form->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
            $this->standard_intro_elements(get_string('description', Constants::EDUSHARING_MODULE_NAME));
            // Repo button and version select are not to be shown for edit form.
            if (!isset($_GET['update'])) {
                $objecttitlehelp = $utils->get_config_entry('enable_repo_target_chooser') ?
                    get_string('object_title_help_chooser', Constants::EDUSHARING_MODULE_NAME) :
                    get_string('object_title_help', Constants::EDUSHARING_MODULE_NAME);
                $this->_form->addElement(
                    'header',
                    'object_url_fieldset',
                    get_string(
                        'object_url_fieldset',
                        Constants::EDUSHARING_MODULE_NAME,
                        get_config('edusharing', 'application_appname')
                    )
                );
                $this->_form->addElement(
                    'static',
                    'object_title',
                    get_string('object_title', Constants::EDUSHARING_MODULE_NAME),
                    $objecttitlehelp
                );
                $this->_form->addElement(
                    'text',
                    'object_url',
                    get_string('object_url', Constants::EDUSHARING_MODULE_NAME),
                    ['readonly' => 'true']
                );
                $this->_form->setType('object_url', PARAM_RAW_TRIMMED);
                $this->_form->addRule('object_url', null, 'required', null, 'client');
                $this->_form->addRule('object_url', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
                $this->_form->addHelpButton('object_url', 'object_url', Constants::EDUSHARING_MODULE_NAME);
                if ($utils->get_config_entry('enable_repo_target_chooser')) {
                    // phpcs:disable -- just messy html and js.
                    $buttongrouphtml = '
                        <div class="btn-group" role="group" aria-label="Repository options">
                            <button id="edu_search_button" type="button" class="btn btn-secondary">' . get_string('repoSearch', 'edusharing') . '</button>
                            <button id="edu_workspace_button" type="button" class="btn btn-secondary">' . get_string('repoWorkspace', 'edusharing') . '</button>
                            <button id="edu_collections_button" type="button" class="btn btn-secondary">' . get_string('repoCollection', 'edusharing') . '</button>
                        </div>
                    ';
                    // phpcs:enable
                    $this->_form->addElement('static', 'repo_buttons', '', $buttongrouphtml);
                } else {
                    $searchbutton = $this->_form->addElement(
                        'button',
                        'edu_search_button',
                        get_string(
                            'searchrec',
                            Constants::EDUSHARING_MODULE_NAME,
                            get_config('edusharing', 'application_appname')
                        )
                    );
                }
                $this->_form->addElement(
                    'header',
                    'version_fieldset',
                    get_string('object_version_fieldset', Constants::EDUSHARING_MODULE_NAME)
                );
                $radiogroup   = [];
                $radiogroup[] = $this->_form->createElement(
                    'radio',
                    'object_version',
                    '',
                    get_string('object_version_use_latest', Constants::EDUSHARING_MODULE_NAME),
                    0,
                    []
                );
                $radiogroup[] = $this->_form->createElement(
                    'radio',
                    'object_version',
                    '',
                    get_string('object_version_use_exact', Constants::EDUSHARING_MODULE_NAME),
                    1,
                    []
                );
                $this->_form->addGroup(
                    $radiogroup,
                    'object_version',
                    get_string('object_version', Constants::EDUSHARING_MODULE_NAME),
                    [' '],
                    false
                );
                $this->_form->setDefault('object_version', 0);
                $this->_form->addHelpButton('object_version', 'object_version', Constants::EDUSHARING_MODULE_NAME);
            }
            // Add standard grading elements.
            $this->standard_grading_coursemodule_elements();
            // Grade method
            $options = Grader::get_grading_methods();
            $this->_form->addElement('select', 'grade_method', get_string('grade_grademethod', 'mod_h5pactivity'), $options);
            $this->_form->setType('grade_method', PARAM_INT);
            $this->_form->disabledIf('grade_method', 'grade[modgrade_type]', 'neq', 'point');
            $this->_form->addHelpButton('grade_method', 'grade_grademethod', 'mod_h5pactivity');

            // Display-section.
            $this->_form->addElement(
                'header',
                'object_display_fieldset',
                get_string('object_display_fieldset', Constants::EDUSHARING_MODULE_NAME)
            );
            $windowoptions =
                [
                    0 => get_string('pagewindow', Constants::EDUSHARING_MODULE_NAME),
                    1 => get_string('newwindow', Constants::EDUSHARING_MODULE_NAME),
                ];
            $this->_form->addElement(
                'select',
                'popup_window',
                get_string('display', Constants::EDUSHARING_MODULE_NAME),
                $windowoptions
            );
            $this->_form->setDefault('popup_window', !empty($CFG->resource_popup));
            // Add standard elements, common to all modules.
            $this->standard_coursemodule_elements();
            $submit2label = get_string('savechangesandreturntocourse');
        } catch (Exception $e) {
            debugging($e->getLine() . ': ' . $e->getMessage());
        }
        $buttons = [];
        if (!empty($submit2label) && $this->courseformat->has_view_page()) {
            $buttons[] = $this->_form->createElement('submit', 'submitbutton2', $submit2label);
        }
        $buttons[] = $this->_form->createElement('cancel');
        $this->_form->addGroup($buttons, 'buttonar', '', [' '], false);
        $this->_form->setType('buttonar', PARAM_RAW);
        $this->_form->closeHeaderBefore('buttonar');
    }
}
