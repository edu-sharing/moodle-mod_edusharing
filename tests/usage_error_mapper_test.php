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

declare(strict_types=1);

// Namespace does not match PSR. But Moodle likes it this way.
namespace mod_edusharing;

use advanced_testcase;
use EduSharingApiClient\AppAuthException;
use EduSharingApiClient\InvalidAppIdException;
use EduSharingApiClient\MissingRightsException;
use Exception;
use moodle_exception;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class usage_error_mapper_test
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\mod_edusharing\UsageErrorMapper::class)]
final class usage_error_mapper_test extends advanced_testcase {
    /**
     * Function test_if_missing_rights_is_mapped_to_the_publish_rights_message
     *
     * @return void
     */
    public function test_if_missing_rights_is_mapped_to_the_publish_rights_message(): void {
        $this->resetAfterTest();
        $result = UsageErrorMapper::to_moodle_exception(new MissingRightsException('technical detail'));
        $this->assertSame('error_usage_no_publish_rights', $result->errorcode);
        $this->assertSame('edusharing', $result->module);
        $this->assertStringContainsString('technical detail', (string)$result->debuginfo);
        $this->assertStringNotContainsString('technical detail', $result->getMessage());
    }

    /**
     * Function test_if_authentication_failures_are_mapped_to_the_auth_message
     *
     * @return void
     */
    public function test_if_authentication_failures_are_mapped_to_the_auth_message(): void {
        $this->resetAfterTest();
        $this->assertSame(
            'error_auth_failed',
            UsageErrorMapper::to_moodle_exception(new AppAuthException('INVALID_HOST'))->errorcode
        );
        $this->assertSame(
            'error_auth_failed',
            UsageErrorMapper::to_moodle_exception(new InvalidAppIdException('bad app id'))->errorcode
        );
    }

    /**
     * Function test_if_unknown_failures_are_mapped_to_the_generic_message
     *
     * @return void
     */
    public function test_if_unknown_failures_are_mapped_to_the_generic_message(): void {
        $this->resetAfterTest();
        $result = UsageErrorMapper::to_moodle_exception(new Exception('repository exploded'));
        $this->assertSame('error_usage_creation_failed', $result->errorcode);
    }

    /**
     * Function test_if_moodle_exceptions_are_passed_through_unchanged
     *
     * A database or capability failure already carries a meaningful message, so it must not
     * be relabelled as a repository error.
     *
     * @return void
     */
    public function test_if_moodle_exceptions_are_passed_through_unchanged(): void {
        $this->resetAfterTest();
        $original = new moodle_exception('invalidrecord', 'error');
        $this->assertSame($original, UsageErrorMapper::to_moodle_exception($original));
        $this->assertSame($original->getMessage(), UsageErrorMapper::get_user_message($original));
    }

    /**
     * Function test_if_get_user_message_returns_the_translated_reason
     *
     * @return void
     */
    public function test_if_get_user_message_returns_the_translated_reason(): void {
        $this->resetAfterTest();
        $this->assertSame(
            get_string('error_usage_no_publish_rights', 'edusharing'),
            UsageErrorMapper::get_user_message(new MissingRightsException('technical detail'))
        );
        $this->assertSame(
            get_string('error_usage_creation_failed', 'edusharing'),
            UsageErrorMapper::get_user_message(new Exception('repository exploded'))
        );
    }
}
