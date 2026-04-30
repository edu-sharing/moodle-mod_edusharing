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

class MoodleSignatureHandler implements SignatureHandler
{
    private EduSharingNodeHelper $nodehelper;

    public function __construct(EduSharingNodeHelper $nodehelper) {
        $this->nodehelper = $nodehelper;
    }

    public function getAlgorithm(): string {
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
            // Do nothing. Just use default.
        }
        $SESSION->edusharing_signing_algorithm = $this->nodehelper->base->defaultAlgorithm;
        return $this->nodehelper->base->defaultAlgorithm;
    }
}
