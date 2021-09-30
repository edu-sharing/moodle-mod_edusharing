<?php

/**
 * Filter converting edu-sharing URIs in the text to edu-sharing rendering links
 *
 * @package mod_edusharing
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/edusharing/locallib.php');

function xmldb_edusharing_install() {
    global $CFG;

    if (file_exists(dirname(__FILE__).'/install_config.php')) {
        require_once dirname(__FILE__). '/install_config.php';
        $metadataurl = REPO_URL.'/metadata?format=lms&external=true';
        $repo_admin = REPO_ADMIN;
        $repo_pw = REPO_PW;
        $appID = MOODLE_APPID;
        $wlo_guestuser = '';

        if (AUTO_APPID_FROM_URL == 'true'){
            $appID = basename($CFG->wwwroot);
        }

        if (WLO_GUESTMODE == 'true'){
            $wlo_guestuser = WLO_GUESTUSER;
        }

        if (edusharing_import_metadata($metadataurl, $appID, MOODLE_HOST_ALIASES, $wlo_guestuser)){
            error_log('Successfully imported metadata from '.$metadataurl);
            $repo_url = get_config('edusharing', 'application_cc_gui_url');
            $data = createXmlMetadata();
            $answer = json_decode(registerPlugin($repo_url, $repo_admin, $repo_pw, $data), true);
            if (isset($answer['appid'])){
                error_log('Successfully registered the edusharing-moodle-plugin at: '.$repo_url);
            }else{
                error_log('INSTALL ERROR: Could not register the edusharing-moodle-plugin at: '.$repo_url.' because: '.$answer['message']);
            }
        }else{
            error_log('INSTALL ERROR: Could not import metadata from '.$metadataurl);
        }
        unlink(dirname(__FILE__).'/install_config.php');
    }

}

