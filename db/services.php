<?php

$functions = [
    'mod_edusharing_add_instance'    => [
        'classname'   => 'mod_edusharing\external\AddInstance',
        'description' => 'adds a new instance',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [
            MOODLE_OFFICIAL_MOBILE_SERVICE
        ]
    ],
    'mod_edusharing_delete_instance' => [
        'classname'   => 'mod_edusharing\external\DeleteInstance',
        'description' => 'Deletes an edu-sharing instance',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [
            MOODLE_OFFICIAL_MOBILE_SERVICE
        ]
    ],
    'mod_edusharing_get_ticket'      => [
        'classname'   => 'mod_edusharing\external\GetTicket',
        'description' => 'fetches the ticket',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [
            MOODLE_OFFICIAL_MOBILE_SERVICE
        ]
    ]
];
