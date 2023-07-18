<?php

/**
 * install.php
 *
 * Performed on every plugin installation
 * Checks for settings in installConfig.json
 * imports metadata and registers plugin with provided data
 *
 * @package mod_edusharing
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_edusharing\InstallUpgradeLogic;

defined('MOODLE_INTERNAL') || die();

function xmldb_edusharing_install(): void {
    InstallUpgradeLogic::perform();
}

