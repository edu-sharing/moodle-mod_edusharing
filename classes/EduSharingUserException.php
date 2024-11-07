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

use Exception;
use Throwable;

/**
 * Class EduSharingUserException
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class EduSharingUserException extends Exception {
    /**
     * @var string|null
     */
    private ?string $htmlmessage;

    /**
     * Function __construct
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param string|null $htmlmessage
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, ?string $htmlmessage = null) {
        $this->htmlmessage = $htmlmessage;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Function getHtmlMessage
     *
     * @return string|null
     */
    public function get_html_message(): ?string {
        return $this->htmlmessage;
    }
}
