<?php declare(strict_types = 1);

namespace mod_edusharing;

use Exception;

class MetaDataFrontend
{
    public static function getRepoForm(): ?string {
        try {
            $repoUrl     = get_config('edusharing', 'application_cc_gui_url') !== false ? get_config('edusharing', 'application_cc_gui_url') : '';
            $appId       = get_config('edusharing', 'application_appid') !== false ? get_config('edusharing', 'application_appid') : '';
            $hostAliases = get_config('edusharing', 'application_host_aliases') !== false ? get_config('edusharing', 'application_host_aliases') : '';
        } catch (Exception $exception) {
            unset($exception);
            return '<p style="background: #FF8170">Error<br></p>';
        }
        if (!empty($repoUrl)){
            return '
            <form class="repo-reg" action="import_metadata.php" method="post">
                <h3>Try to register the edu-sharing moodle-plugin with a repository:</h3>
                <p>If your moodle is behind a proxy-server, this might not work and you have to register the plugin manually.</p>
                <div class="edu_metadata">
                    <div class="repo_input">
                        <p class="repo_input_name">Repo-URL:</p><input type="text" value="'. $repoUrl .'" name="repoUrl" />
                    </div>
                    <div class="repo_input">
                        <p class="repo_input_name">Repo-Admin-User:</p><input class="short_input" type="text" name="repoAdmin">
                        <p class="repo_input_name">Repo-Admin-Password:</p><input class="short_input" type="password" name="repoPwd">
                    </div>
                    <div class="repo_input">
                        <p class="repo_input_name">Change Moodle-AppID:</p><input type="text" value="'. $appId .'" name="appId" />
                        <p>(optional)</p>
                    </div>
                    <div class="repo_input">
                        <p class="repo_input_name">Add Host-Alias:</p><input type="text" value="'. $hostAliases .'" name="host_aliases" />
                        <p>(optional)</p>
                    </div>
                    <input class="btn" type="submit" value="Register Repo" name="repoReg">
                </div>            
            </form>
         ';
        }
        return null;
    }

    /**
     * Function getMetaDataForm
     *
     * Returns the form to input the metadata url
     *
     * @return string
     */
    public static function getMetaDataForm(): string {
        global $CFG;
        return '
        <form action="import_metadata.php" method="post" name="mdform">
            <h3>Enter your metadata endpoint here:</h3>
            <p>Hint: Just click on the example to copy it into the input-field.</p>
            <div class="edu_metadata">                
                <div class="edu_endpoint">
                    <p>Metadata-Endpoint:</p>
                    <input type="text" id="metadata" name="mdataurl" value="">
                    <input class="btn" type="submit" value="Import">
                </div>
                <div class="edu_example">
                    <p>(Example: <a href="javascript:void();"
                                   onclick="document.forms[0].mdataurl.value=\'http://your-server-name/edu-sharing/metadata?format=lms&external=true\'">
                                   http://your-server-name/edu-sharing/metadata?format=lms&external=true</a>)
                   </p>
                </div>
            </div>
        </form>
        <p>To export the edu-sharing plugin metadata use the following url: <span class="edu_export">' . $CFG->wwwroot . '/mod/edusharing/metadata.php</span></p>';
    }
}