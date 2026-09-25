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

use core_user;
use stdClass;
use Throwable;

/**
 * Class RestoreRightsChecker
 *
 * Finds out ahead of a course restore whether the restoring user may create usages
 * for the edu-sharing objects in the backup. A usage can only be created with the
 * CCPublish permission, so objects lacking it are stripped from the restored course.
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class RestoreRightsChecker {
    /**
     * The permission needed to create a usage
     */
    public const PUBLISH_PERMISSION = 'CCPublish';

    /**
     * Results of earlier checks within this request, keyed by "userid:nodeid"
     *
     * Shared across instances, as the restore hook, the activity tasks and the
     * restore helper each check the same objects.
     *
     * @var bool[]
     */
    private static array $results = [];

    /**
     * Tickets fetched within this request, keyed by userid (null being the current user)
     *
     * @var string[]
     */
    private static array $tickets = [];

    /**
     * @var EduSharingService|null
     */
    private ?EduSharingService $service;

    /**
     * @var UtilityFunctions
     */
    private UtilityFunctions $utils;

    /**
     * RestoreRightsChecker constructor
     *
     * @param EduSharingService|null $service created once a node is actually checked if null
     * @param UtilityFunctions|null $utils
     */
    public function __construct(?EduSharingService $service = null, ?UtilityFunctions $utils = null) {
        $this->service = $service;
        $this->utils   = $utils ?? new UtilityFunctions();
    }

    /**
     * Function reset_cache
     *
     * @return void
     */
    public static function reset_cache(): void {
        self::$results = [];
        self::$tickets = [];
    }

    /**
     * Function can_publish
     *
     * Any failure along the way, be it fetching the ticket or the node, counts as missing rights,
     * as the usage could not be created either
     *
     * @param string $nodeid
     * @param int|null $userid the restoring user, the current user if null
     * @return bool
     */
    public function can_publish(string $nodeid, ?int $userid): bool {
        if ($nodeid === '') {
            return false;
        }
        $key = ($userid ?? '') . ':' . $nodeid;
        if (!array_key_exists($key, self::$results)) {
            try {
                $node = $this->get_service()->get_node_for_user($this->get_ticket($userid), $nodeid);
                self::$results[$key] = in_array(self::PUBLISH_PERMISSION, $node['node']['access'] ?? [], true);
            } catch (Throwable $exception) {
                debugging('Checking publish rights failed for node ' . $nodeid . ': ' . $exception->getMessage());
                self::$results[$key] = false;
            }
        }
        return self::$results[$key];
    }

    /**
     * Function get_service
     *
     * @return EduSharingService
     * @throws \dml_exception
     */
    private function get_service(): EduSharingService {
        return $this->service ??= new EduSharingService();
    }

    /**
     * Function get_ticket
     *
     * @param int|null $userid
     * @return string
     * @throws \Exception
     */
    private function get_ticket(?int $userid): string {
        global $CFG;
        $key = $userid ?? '';
        if (!isset(self::$tickets[$key])) {
            if ($userid === null) {
                self::$tickets[$key] = $this->get_service()->get_ticket();
            } else {
                require_once($CFG->dirroot . '/user/profile/lib.php');
                $user = core_user::get_user($userid, '*', MUST_EXIST);
                profile_load_custom_fields($user);
                self::$tickets[$key] = $this->get_service()->get_ticket_for_user($user);
            }
        }
        return self::$tickets[$key];
    }

    /**
     * Function get_denied_objects
     *
     * @param array $objects as returned by collect_backup_objects
     * @param int|null $userid
     * @return array the objects the user lacks publish rights for
     */
    public function get_denied_objects(array $objects, ?int $userid): array {
        return array_values(array_filter($objects, fn(array $object): bool => !$this->can_publish($object['nodeid'], $userid)));
    }

    /**
     * Function collect_backup_objects
     *
     * Lists the edu-sharing objects of an extracted backup: edu-sharing activities as well as
     * objects embedded in section summaries and activity descriptions, which are the texts
     * RestoreHelper::convert_inline_options converts after the restore
     *
     * @param string $basepath the directory the backup has been extracted to
     * @param stdClass $info the backup information
     * @return array of ['nodeid' => string, 'title' => string, 'kind' => 'activity'|'text', 'moduleid' => ?int]
     */
    public function collect_backup_objects(string $basepath, stdClass $info): array {
        $objects = [];
        foreach ($info->sections ?? [] as $section) {
            $xml = $this->load_xml($basepath . '/' . $section->directory . '/section.xml');
            if ($xml !== null) {
                $objects = array_merge($objects, $this->collect_text_objects((string)($xml->summary ?? '')));
            }
        }
        foreach ($info->activities ?? [] as $activity) {
            $xml = $this->load_xml($basepath . '/' . $activity->directory . '/' . $activity->modulename . '.xml');
            if ($xml === null) {
                continue;
            }
            $instance = $xml->{$activity->modulename};
            if ($activity->modulename === 'edusharing') {
                $objects[] = [
                    'nodeid'   => $this->utils->get_object_id_from_url((string)($instance->object_url ?? '')),
                    'title'    => (string)($instance->name ?? $activity->title),
                    'kind'     => 'activity',
                    'moduleid' => (int)$activity->moduleid,
                ];
            }
            $objects = array_merge($objects, $this->collect_text_objects((string)($instance->intro ?? '')));
        }
        return $objects;
    }

    /**
     * Function collect_text_objects
     *
     * @param string $text
     * @return array
     */
    private function collect_text_objects(string $text): array {
        if (!str_contains($text, 'edusharing_atto')) {
            return [];
        }
        $objects = [];
        foreach ($this->utils->get_inline_object_matches($text)['rendermatches'] as $match) {
            $params = RestoreHelper::get_inline_object_params($match);
            if ($params === null) {
                continue;
            }
            $objects[] = [
                'nodeid'   => RestoreHelper::get_inline_object_nodeid($params),
                'title'    => (string)($params['title'] ?? ''),
                'kind'     => 'text',
                'moduleid' => null,
            ];
        }
        return $objects;
    }

    /**
     * Function load_xml
     *
     * @param string $path
     * @return \SimpleXMLElement|null
     */
    private function load_xml(string $path): ?\SimpleXMLElement {
        if (!is_readable($path)) {
            return null;
        }
        $xml = simplexml_load_file($path);
        return $xml === false ? null : $xml;
    }
}
