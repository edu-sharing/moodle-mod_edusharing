<?php declare(strict_types=1);

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
class MoodleAboutApiCacheHandler implements AboutApiCacheHandler
{
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
        $cache = \cache::make('mod_edusharing', 'about');
        $about = $cache->get('about');
        if ($about === false) {
            $about = $this->nodehelper->base->getAbout();
            $cache->set('about', $about);
        }
        return $about;
    }
}
