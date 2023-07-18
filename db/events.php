<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_module_deleted',
        'callback' => 'EdusharingObserver::courseModuleDeleted',
        'priority ' => 5000,
    ],
    [
        'eventname' => '\core\event\course_module_created',
        'callback' => 'EdusharingObserver::courseModuleCreatedOrUpdated',
    ],
    [
        'eventname' => '\core\event\course_module_updated',
        'callback' => 'EdusharingObserver::courseModuleCreatedOrUpdated',
    ],
    [
        'eventname' => '\core\event\course_section_created',
        'callback' => 'EdusharingObserver::courseSectionUpdatedOrCreated',
    ],
    [
        'eventname' => '\core\event\course_section_updated',
        'callback' => 'EdusharingObserver::courseSectionUpdatedOrCreated',
    ],
    [
        'eventname' => 'core\event\course_deleted',
        'callback' => 'EdusharingObserver::courseDeleted',
    ],
    [
        'eventname' => '\core\event\course_restored',
        'callback' => 'EdusharingObserver::courseRestored',
    ]
];
