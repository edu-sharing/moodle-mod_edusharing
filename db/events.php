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
 * events
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_module_deleted',
        'callback' => 'mod_edusharing_observer::course_module_deleted',
        'priority ' => 5000,
    ],
    [
        'eventname' => '\core\event\course_module_created',
        'callback' => 'mod_edusharing_observer::course_module_created',
    ],
    [
        'eventname' => '\core\event\course_module_updated',
        'callback' => 'mod_edusharing_observer::course_module_updated',
    ],
    [
        'eventname' => '\core\event\course_section_created',
        'callback' => 'mod_edusharing_observer::course_section_created',
    ],
    [
        'eventname' => '\core\event\course_section_updated',
        'callback' => 'mod_edusharing_observer::course_section_updated',
    ],
    [
        'eventname' => 'core\event\course_deleted',
        'callback' => 'mod_edusharing_observer::course_deleted',
    ],
    [
        'eventname' => '\core\event\course_restored',
        'callback' => 'mod_edusharing_observer::course_restored',
    ],
];
