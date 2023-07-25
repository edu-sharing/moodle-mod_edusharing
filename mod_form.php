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

use mod_edusharing\EduSharingService;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * The main edusharing configuration form
 *
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_edusharing_mod_form extends moodleform_mod
{
    /**
     * (non-PHPdoc)
     * @see lib/moodleform::definition()
     */
    public function definition(): void {
        try {
            $eduSharingService = new EduSharingService();
            $ticket            = $eduSharingService->getTicket();
            // Adding the "general" fieldset, where all the common settings are showed
            $this->_form->addElement('header', 'general', get_string('general', 'form'));
            // Adding the standard "name" field
            $this->_form->addElement('text', 'name', get_string('edusharingname', EDUSHARING_MODULE_NAME), ['size' => '64']);
            $this->_form->setType('name', PARAM_TEXT);
            $this->_form->addRule('name', null, 'required', null, 'client');
            $this->_form->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
            $this->standard_intro_elements(get_string('description', EDUSHARING_MODULE_NAME));
            // object-section
            $this->_form->addElement('header', 'object_url_fieldset', get_string('object_url_fieldset', EDUSHARING_MODULE_NAME, get_config('edusharing', 'application_appname')));
            $this->_form->addElement('static', 'object_title', get_string('object_title', EDUSHARING_MODULE_NAME), get_string('object_title_help', EDUSHARING_MODULE_NAME));
            // object-uri
            $this->_form->addElement('text', 'object_url', get_string('object_url', EDUSHARING_MODULE_NAME), ['readonly' => 'true']);
            $this->_form->setType('object_url', PARAM_RAW_TRIMMED);
            $this->_form->addRule('object_url', null, 'required', null, 'client');
            $this->_form->addRule('object_url', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
            $this->_form->addHelpButton('object_url', 'object_url', EDUSHARING_MODULE_NAME);
            $searchUrl = get_config('edusharing', 'application_cc_gui_url');
            $searchUrl = str_contains($searchUrl, '-service') ? 'http://repository.127.0.0.1.nip.io:8100/edu-sharing' : $searchUrl;
            $repoSearch = trim($searchUrl, '/') . '/components/search?&applyDirectories=true&reurl=WINDOW&ticket=' . $ticket;
            $searchButton = $this->_form->addElement('button', 'searchbutton', get_string('searchrec', EDUSHARING_MODULE_NAME, get_config('edusharing', 'application_appname')));
            $repoOnClick = "
                            function openRepo(){
                                window.addEventListener('message', function handleRepo(event) {
                                    if (event.data.event == 'APPLY_NODE') {
                                        const node = event.data.data;
                                        window.console.log(node);
                                        window.win.close();
                                        
                                        window.document.getElementById('id_object_url').value = node.objectUrl;
                                        let title = node.title;
                                        if(!title){
                                            title = node.properties['cm:name'];
                                        }
                                        
                                        window.document.getElementById('fitem_id_object_title').getElementsByClassName('form-control-static')[0].innerHTML = title;
                                        
                                        if(window.document.getElementById('id_name').value === ''){
                                            window.document.getElementById('id_name').value = title;
                                        }
                                        
                                        window.removeEventListener('message', handleRepo, false );
                                    }                                    
                                }, false);
                                window.win = window.open('".$repoSearch."');                                                          
                            }
                            openRepo();
                        ";
            $searchButton->updateAttributes(['title' => get_string('uploadrec', EDUSHARING_MODULE_NAME, get_config('edusharing', 'application_appname')), 'onclick' => $repoOnClick]);
            // version-section
            $this->_form->addElement('header', 'version_fieldset', get_string('object_version_fieldset', EDUSHARING_MODULE_NAME));
            $radioGroup   = [];
            $radioGroup[] = $this->_form->createElement('radio', 'object_version', '', get_string('object_version_use_latest', EDUSHARING_MODULE_NAME), 0, []);
            $radioGroup[] = $this->_form->createElement('radio', 'object_version', '', get_string('object_version_use_exact', EDUSHARING_MODULE_NAME), 1, []);
            $this->_form->addGroup($radioGroup, 'object_version', get_string('object_version', EDUSHARING_MODULE_NAME), [' '], false);
            $this->_form->setDefault('object_version', 0);
            $this->_form->addHelpButton('object_version', 'object_version', EDUSHARING_MODULE_NAME);
            // display-section
            $this->_form->addElement('header', 'object_display_fieldset', get_string('object_display_fieldset', EDUSHARING_MODULE_NAME));
            $windowOptions = [0  => get_string('pagewindow', EDUSHARING_MODULE_NAME), 1  => get_string('newwindow', EDUSHARING_MODULE_NAME)];
            $this->_form->addElement('select', 'popup_window', get_string('display', EDUSHARING_MODULE_NAME), $windowOptions);
            $this->_form->setDefault('popup_window', !empty($CFG->resource_popup));
            // add standard elements, common to all modules
            $this->standard_coursemodule_elements();
            $submit2label = get_string('savechangesandreturntocourse');
        } catch (Exception $e) {
            var_dump('+++++file++++++');
            var_dump($e->getFile());
            var_dump('+++++line++++++');
            var_dump($e->getLine());
            var_dump('+++++message++++++');
            var_dump($e->getMessage());
            die;
        }
        $buttons = [];
        if (! empty($submit2label) && $this->courseformat->has_view_page()) {
            $buttons[] = $this->_form->createElement('submit', 'submitbutton2', $submit2label);
        }
        $buttons[] = $this->_form->createElement('cancel');
        $this->_form->addGroup($buttons, 'buttonar', '', [' '], false);
        $this->_form->setType('buttonar', PARAM_RAW);
        $this->_form->closeHeaderBefore('buttonar');
    }
}
