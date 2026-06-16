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

use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\SignatureHandler;
use Exception;

/**
 * class MoodleSignatureHandler
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleSignatureHandler implements SignatureHandler
{
    /**
     * @var EduSharingNodeHelper
     */
    private EduSharingNodeHelper $nodehelper;

    /**
     * MoodleSignatureHandler constructor
     *
     * @param EduSharingNodeHelper $nodehelper
     */
    public function __construct(EduSharingNodeHelper $nodehelper) {
        $this->nodehelper = $nodehelper;
    }

    // phpcs:disable -- Function cannot be lowercase as it implements an interface
    /**
     * Function getAlgorithm
     *
     * @return string
     */
    public function getAlgorithm(): string {
        // phpcs:enable
        global $SESSION;
        if (isset($SESSION->edusharing_signing_algorithm)) {
            return $SESSION->edusharing_signing_algorithm;
        }
        try {
            $about = $this->nodehelper->base->getAbout();
            if (isset($about['signatureAlgorithms'])) {
                $SESSION->edusharing_signing_algorithm = 'SHA512withRSA';
                return 'SHA512withRSA';
            }
        } catch (Exception) {
            unset($exception);
        }
        $SESSION->edusharing_signing_algorithm = $this->nodehelper->base->defaultAlgorithm;
        return $this->nodehelper->base->defaultAlgorithm;
    }
}
