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

use mod_edusharing\Constants;
use mod_edusharing\EduSharingService;

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
        try {
            $edusharingservice = new EduSharingService();
            $ticket            = $edusharingservice->get_ticket();
            // Adding the "general" fieldset, where all the common settings are shown.
            $this->_form->addElement('header', 'general', get_string('general', 'form'));
            // Adding the standard "name" field.
            $this->_form->addElement('text', 'name',
                get_string('edusharingname', Constants::EDUSHARING_MODULE_NAME), ['size' => '64']);
            $this->_form->setType('name', PARAM_TEXT);
            $this->_form->addRule('name', null, 'required', null, 'client');
            $this->_form->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
            $this->standard_intro_elements(get_string('description', Constants::EDUSHARING_MODULE_NAME));
            // Repo button and version select are not to be shown for edit form
            if (!isset($_GET['update'])) {
                $this->_form->addElement('header', 'object_url_fieldset',
                    get_string('object_url_fieldset', Constants::EDUSHARING_MODULE_NAME,
                        get_config('edusharing', 'application_appname')));
                $this->_form->addElement('static', 'object_title',
                    get_string('object_title', Constants::EDUSHARING_MODULE_NAME),
                    get_string('object_title_help', Constants::EDUSHARING_MODULE_NAME));
                $this->_form->addElement('text', 'object_url',
                    get_string('object_url', Constants::EDUSHARING_MODULE_NAME), ['readonly' => 'true']);
                $this->_form->setType('object_url', PARAM_RAW_TRIMMED);
                $this->_form->addRule('object_url', null, 'required', null, 'client');
                $this->_form->addRule('object_url', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
                $this->_form->addHelpButton('object_url', 'object_url', Constants::EDUSHARING_MODULE_NAME);
                $searchurl    = get_config('edusharing', 'application_cc_gui_url');
                $reposearch   = trim($searchurl, '/') . '/components/search?&applyDirectories=false&reurl=WINDOW&ticket=' . $ticket;
                $searchbutton = $this->_form->addElement('button', 'searchbutton',
                    get_string('searchrec', Constants::EDUSHARING_MODULE_NAME,
                        get_config('edusharing', 'application_appname')));
                $repoonclick  = "
                            function openRepo(){
                                window.addEventListener('message', function handleRepo(event) {
                                    if (event.data.event == 'APPLY_NODE') {
                                        const node = event.data.data;
                                        window.win.close();
                                        window.document.getElementById('id_object_url').value = node.objectUrl;
                                        let title = node.title;
                                        if(!title){
                                            title = node.properties['cm:name'];
                                        }
                                        let version = node.properties['cclom:version'][0];
                                        if (version) {
                                            window.document.getElementById('id_object_version_1').value = version;
                                        }
                                        let aspects = node.aspects;
                                        if (aspects.includes('ccm:published') || aspects.includes('ccm:collection_io_reference')) {
                                            window.document.getElementById('id_object_version_0').checked = true;
                                            window.document.getElementById('id_object_version_1').closest('label').hidden = true;
                                        }
                                        window.document.getElementById('fitem_id_object_title')
                                            .getElementsByClassName('form-control-static')[0].innerHTML = title;
                                        if(window.document.getElementById('id_name').value === ''){
                                            window.document.getElementById('id_name').value = title;
                                        }
                                        window.removeEventListener('message', handleRepo, false );
                                    }
                                }, false);
                                window.win = window.open('" . $reposearch . "');
                            }
                            openRepo();
                        ";
                $searchbutton->updateAttributes(
                    [
                        'title' => get_string('uploadrec', Constants::EDUSHARING_MODULE_NAME,
                            get_config('edusharing', 'application_appname')),
                        'onclick' => $repoonclick,
                    ]
                );
                $this->_form->addElement('header', 'version_fieldset',
                    get_string('object_version_fieldset', Constants::EDUSHARING_MODULE_NAME));
                $radiogroup   = [];
                $radiogroup[] = $this->_form->createElement('radio', 'object_version', '',
                    get_string('object_version_use_latest', Constants::EDUSHARING_MODULE_NAME), 0, []);
                $radiogroup[] = $this->_form->createElement('radio', 'object_version', '',
                    get_string('object_version_use_exact', Constants::EDUSHARING_MODULE_NAME), 1, []);
                $this->_form->addGroup($radiogroup, 'object_version',
                    get_string('object_version', Constants::EDUSHARING_MODULE_NAME), [' '], false);
                $this->_form->setDefault('object_version', 0);
                $this->_form->addHelpButton('object_version', 'object_version', Constants::EDUSHARING_MODULE_NAME);
            }
            // Display-section.
            $this->_form->addElement('header', 'object_display_fieldset',
                get_string('object_display_fieldset', Constants::EDUSHARING_MODULE_NAME));
            $windowoptions =
                [
                    0 => get_string('pagewindow', Constants::EDUSHARING_MODULE_NAME),
                    1 => get_string('newwindow', Constants::EDUSHARING_MODULE_NAME),
                ];
            $this->_form->addElement('select', 'popup_window', get_string('display', Constants::EDUSHARING_MODULE_NAME), $windowoptions);
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
