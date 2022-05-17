<?php
defined('MOODLE_INTERNAL') || die();

class mod_edusharing_restorehelper {

    public static function edusharing_convert_inline_objects($courseId) {
        global $DB;

        $sections = $DB -> get_records('course_sections', array('course' => $courseId));
        foreach ($sections as $section) {
            $matches_atto = self::edusharing_get_inline_objects($section->summary);
            if (!empty($matches_atto)) {
                foreach ($matches_atto as $match) {
                    $section -> summary = str_replace($match, self::edusharing_convert_object($match, $courseId), $section -> summary);
                    $DB -> update_record('course_sections', $section);
                }
            }
        }


        $modules = get_course_mods($courseId);
        $course = get_course($courseId);
        foreach($modules as $module) {
            $modinfo = get_fast_modinfo($course);
            $cm = $modinfo -> get_cm($module -> id);
            if(!empty($cm -> content)) {
                $matches_atto = self::edusharing_get_inline_objects($cm->content);
                if (!empty($matches_atto)) {
                    foreach ($matches_atto as $match) {
                        $cm -> set_content(str_replace($match, self::edusharing_convert_object($match, $courseId), $cm->content));
                    }
                }
            }
            $module = $DB->get_record($cm->name,array('id' => $cm->instance));
            if(!empty($module -> intro)) {
                $matches_atto = self::edusharing_get_inline_objects($module -> intro);
                if (!empty($matches_atto)) {
                    foreach ($matches_atto as $match) {
                        $module -> intro = str_replace($match, self::edusharing_convert_object($match, $courseId), $module -> intro);
                    }
                }
            }
            $DB -> update_record($cm -> name, $module);

        }
        rebuild_course_cache($courseId, true);
    }

    public static function edusharing_get_inline_objects($text) {

        try {

            if (strpos($text, 'edusharing_atto') === false) {
                return;
            }

            // Ensure that user exists in repository.
            if (isloggedin()) {
                if (!empty(get_config('edusharing', 'repository_restApi'))) {
                    $eduSharingService = new EduSharingService();
                    $ticket = $eduSharingService->getTicket();
                }else{
                    $ccauth = new mod_edusharing_web_service_factory();
                    $ticket = $ccauth->edusharing_authentication_get_ticket();
                }
            }

            preg_match_all('#<img(.*)class="(.*)edusharing_atto(.*)"(.*)>#Umsi', $text, $matchesimg_atto,
                PREG_PATTERN_ORDER);
            preg_match_all('#<a(.*)class="(.*)edusharing_atto(.*)">(.*)</a>#Umsi', $text, $matchesa_atto,
                PREG_PATTERN_ORDER);
            return array_merge($matchesimg_atto[0], $matchesa_atto[0]);

        } catch (Exception $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);
        }
    }

    public static function edusharing_convert_object($object, $courseId) {
        global $DB;

        $doc = new DOMDocument();
        $doc->loadHTML($object, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $node = $doc->getElementsByTagName('a')->item(0);
        $type = 'a';
        if (empty($node)) {
            $node = $doc->getElementsByTagName('img')->item(0);
            $qs = $node->getAttribute('src');
            $type = 'img';
        } else {
            $qs = $node->getAttribute('href');
        }

        if (empty($node)) {
            throw Exception(get_string('error_loading_node', 'filter_edusharing'));
        }

        $params = array();
        parse_str(parse_url($qs, PHP_URL_QUERY), $params);

        $edusharing = new stdClass();
        $edusharing -> course = $courseId;
        $edusharing -> name = $params['title'];
        $edusharing -> introformat = 0;
        $edusharing -> object_url = $params['object_url'];
        $edusharing -> object_version = $params['window_version'];
        $edusharing -> timecreated = time();
        $edusharing -> timemodified = time();

        $id = $DB -> insert_record('edusharing', $edusharing);

        if($id) {
            $usage = self::edusharing_add_usage($edusharing, $id);
            if (isset($usage->usageId)) {
                $edusharing->id = $id;
                $edusharing->usageId = $usage->usageId;
                $DB->update_record(EDUSHARING_TABLE, $edusharing);
            }

            if ($usage) {
                $params['resourceId'] = $id;
                $url = strtok($qs, '?') . '?';
                foreach ($params as $paramn => $paramv) {
                    $url .= $paramn . '=' . $paramv . '&';
                }
                if ($type === 'a')
                    $node->setAttribute('href', $url);
                else
                    $node->setAttribute('src', $url);

            } else {
                $DB->delete_records('edusharing', array('id' => $id));
                return $object;
            }
        }
        return $doc -> saveHTML();
    }

    public static function edusharing_add_usage($data, $newitemid) {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/mod/edusharing/locallib.php');

        if (!empty(get_config('edusharing', 'repository_restApi'))) {
            $eduService = new EduSharingService();
            $usageData   = new stdClass ();

            $usageData->ticket       = $eduService->getTicket();
            $usageData->containerId  = $data->course;
            $usageData->resourceId   = $newitemid;
            $usageData->nodeId       = edusharing_get_object_id_from_url($data->object_url);
            $usageData->nodeVersion  = $data->object_version;

            return $eduService -> createUsage( $usageData );
        }else{
            $client = new mod_edusharing_sig_soap_client(get_config('edusharing', 'repository_usagewebservice_wsdl'), array());
            $xml = edusharing_get_usage_xml($data);
            $params = array(
                "eduRef" => $data->object_url,
                "user" => edusharing_get_auth_key(),
                "lmsId" => get_config('edusharing', 'application_appid'),
                "courseId" => $data->course,
                "userMail" => $USER->email,
                "fromUsed" => '2002-05-30T09:00:00',
                "toUsed" => '2222-05-30T09:00:00',
                "distinctPersons" => '0',
                "version" => $data->object_version,
                "resourceId" => $newitemid,
                "xmlParams" => $xml,
            );
            $client->setUsage($params);

            return true;
        }
    }

}
