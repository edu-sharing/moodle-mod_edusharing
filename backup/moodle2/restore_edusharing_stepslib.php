<?php
/**
 * Structure step to restore one edusharing activity
 */


use mod_edusharing\EduSharingService;
use mod_edusharing\RestoreHelper;

require_once(dirname(__FILE__).'/../../lib.php');


class restore_edusharing_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('edusharing', '/activity/edusharing');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_edusharing($data) {
        global $DB;

        $data = (object)$data;
        try {
            $data->course = $this->get_courseid();
            // insert the edusharing record
            $newId = $DB->insert_record('edusharing', $data);
            // immediately after inserting "activity" record, call this
            $helper = new RestoreHelper(new EduSharingService());
            $helper->addUsage($data, $newId);
            $this->apply_activity_instance($newId);
        } catch (Exception $exception) {
            try {
                isset($newId) && $DB->delete_records('edusharing', ['id' => $newId]);
                $message = str_contains($exception->getMessage(), 'NO_CCPUBLISH_PERMISSION') ? get_string('exc_NO_PUBLISH_RIGHTS', 'edusharing') : $exception->getMessage();
                $this->log($message, backup::LOG_ERROR, null, null, true);
            } catch (Exception $stupidException) {
                // Well, there is only so much we can do...
                unset($stupidException);
            }
        }
    }

    protected function after_execute() {
    }
}
