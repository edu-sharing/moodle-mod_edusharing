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

use coding_exception;
use EduSharingApiClient\AppAuthException;
use EduSharingApiClient\InvalidAppIdException;
use EduSharingApiClient\MissingRightsException;
use moodle_exception;
use Throwable;

/**
 * Class UsageErrorMapper
 *
 * Translates the exceptions that can arise while registering a usage in the edu-sharing
 * repository into messages that can be shown to the user.
 *
 * The technical message is kept as debug info only, so it reaches developers via Moodle's
 * debugging output without being presented to teachers.
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class UsageErrorMapper {
    /**
     * Function get_error_code
     *
     * Determines the language string key describing the given failure.
     *
     * @param Throwable $exception
     * @return string
     */
    private static function get_error_code(Throwable $exception): string {
        if ($exception instanceof MissingRightsException) {
            return 'error_usage_no_publish_rights';
        }
        if ($exception instanceof AppAuthException || $exception instanceof InvalidAppIdException) {
            return 'error_auth_failed';
        }
        return 'error_usage_creation_failed';
    }

    /**
     * Function get_user_message
     *
     * Returns the translated reason, for use in notifications.
     *
     * @param Throwable $exception
     * @return string
     * @throws coding_exception
     */
    public static function get_user_message(Throwable $exception): string {
        if ($exception instanceof moodle_exception) {
            // Database, capability and similar failures already carry a meaningful message.
            return $exception->getMessage();
        }
        return get_string(self::get_error_code($exception), Constants::EDUSHARING_MODULE_NAME);
    }

    /**
     * Function to_moodle_exception
     *
     * Wraps the given failure in a moodle_exception carrying the translated reason.
     *
     * @param Throwable $exception
     * @return moodle_exception
     */
    public static function to_moodle_exception(Throwable $exception): moodle_exception {
        if ($exception instanceof moodle_exception) {
            // Database, capability and similar failures already carry a meaningful message.
            return $exception;
        }
        return new moodle_exception(
            self::get_error_code($exception),
            Constants::EDUSHARING_MODULE_NAME,
            '',
            null,
            $exception->getMessage()
        );
    }
}
