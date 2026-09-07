<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

declare(strict_types=1);

namespace testUtils;

use mod_edusharing\EduSharingService;

/**
 * Class TestableObserver
 *
 * Test double for mod_edusharing_observer that lets unit tests inject a mocked
 * EduSharingService instead of instantiating one that talks to the remote
 * repository.
 *
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class TestableObserver extends \mod_edusharing_observer {
    /**
     * @var EduSharingService the service returned by the overridden factory
     */
    public static EduSharingService $servicemock;

    /**
     * Function get_service
     *
     * @return EduSharingService
     */
    protected static function get_service(): EduSharingService {
        return self::$servicemock;
    }
}
