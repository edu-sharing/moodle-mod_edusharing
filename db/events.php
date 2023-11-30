<?php
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
