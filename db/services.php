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

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_edusharing_add_instance'    => [
        'classname'   => 'mod_edusharing\external\AddInstance',
        'description' => 'adds a new instance',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],
    'mod_edusharing_delete_instance' => [
        'classname'   => 'mod_edusharing\external\DeleteInstance',
        'description' => 'Deletes an edu-sharing instance',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],
    'mod_edusharing_get_ticket'      => [
        'classname'   => 'mod_edusharing\external\GetTicket',
        'description' => 'fetches the ticket',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],
    'mod_edusharing_update_instance'      => [
        'classname'   => 'mod_edusharing\external\UpdateInstance',
        'description' => 'Updates one edu-sharing instance',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],
];
