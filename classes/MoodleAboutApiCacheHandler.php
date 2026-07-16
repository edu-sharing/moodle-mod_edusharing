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

namespace mod_edusharing;

use core\exception\coding_exception;
use EduSharingApiClient\AboutApiCacheHandler;
use EduSharingApiClient\EduSharingNodeHelper;

/**
 * class MoodleAboutApiCacheHandler
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleAboutApiCacheHandler implements AboutApiCacheHandler {
    /**
     * @var EduSharingNodeHelper
     */
    private EduSharingNodeHelper $nodehelper;

    /**
     * MoodleAboutApiCacheHandler constructor
     *
     * @param EduSharingNodeHelper $nodehelper
     */
    public function __construct(EduSharingNodeHelper $nodehelper) {
        $this->nodehelper = $nodehelper;
    }

    // phpcs:disable -- Function cannot be lowercase as it implements an interface.
    /**
     * Returns the repository _about response, cached at application level.
     *
     * On a cache miss (or after the TTL expires) the live /rest/_about
     * endpoint is queried once and the result is stored for subsequent calls.
     *
     * @return array
     * @throws \JsonException
     * @throws coding_exception
     */
    public function getAboutApiCache(): array {
        // phpcs:enable
        $cache = \cache::make('mod_edusharing', 'about');
        $about = $cache->get('about');
        if ($about === false) {
            $about = $this->nodehelper->base->getAbout();
            $cache->set('about', $about);
        }
        return $about;
    }
}
