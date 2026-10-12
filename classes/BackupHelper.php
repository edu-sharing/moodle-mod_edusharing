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

use Throwable;

/**
 * Class BackupHelper
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class BackupHelper {
    /**
     * Function complete_inline_objects
     *
     * The records of embedded objects are not part of a backup, only the text is. Objects the Tiny editor created
     * from a pasted edu-sharing embed iframe carry no title, object url and version in their url, so the restore
     * could not recover the version they were pinned to. This completes the urls in the text of the backup with the
     * values of the objects' records. Tiny also stores the inserting user's repository ticket in the url, which is
     * removed from every object, as a backup must not carry it. The live text on this site is not touched.
     *
     * @param string $content a text of the backup
     * @return string
     */
    public static function complete_inline_objects(string $content): string {
        if (!str_contains($content, 'edusharing_atto') || !str_contains($content, 'preview.php')) {
            return $content;
        }
        $utils = new UtilityFunctions();
        foreach ($utils->get_inline_object_matches($content)['rendermatches'] as $match) {
            try {
                $completed = self::complete_object($match, $utils);
                if ($completed !== null) {
                    $content = str_replace($match, $completed, $content);
                }
            } catch (Throwable $throwable) {
                debugging('Could not complete edu-sharing object for backup: ' . $throwable->getMessage());
            }
        }
        return $content;
    }

    /**
     * Function complete_object
     *
     * Removes the repository ticket from the url of the object, and adds the missing title, object url and version
     * from the object's record. Only the ticket segment is cut from the raw query and the completed values are
     * appended, so all other params stay byte for byte as they are. The record is only looked up if something is missing.
     *
     * @param string $match the html of an embedded object
     * @param UtilityFunctions $utils
     * @return string|null the changed html, null if nothing is to be changed
     */
    private static function complete_object(string $match, UtilityFunctions $utils): ?string {
        global $DB;
        $parsed = RestoreHelper::parse_inline_object($match);
        if ($parsed === null) {
            return null;
        }
        ['doc' => $doc, 'node' => $node, 'type' => $type, 'params' => $params] = $parsed;
        $attribute = $type === 'a' ? 'href' : 'src';
        $url       = $node->getAttribute($attribute);
        $base      = strstr($url, '?', true);
        if ($base === false) {
            return null;
        }
        $segments = explode('&', substr($url, strlen($base) + 1));
        $rest     = preg_grep('/^ticket(?:=|$)/', $segments, PREG_GREP_INVERT);
        $missing  = [];
        if (!isset($params['title'], $params['object_url'], $params['window_version'])) {
            $row = $DB->get_record('edusharing', ['id' => (int)($params['resourceId'] ?? 0)]);
            if ($row && $utils->get_object_id_from_url($row->object_url) === (string)($params['nodeId'] ?? '')) {
                foreach (['title' => $row->name, 'object_url' => $row->object_url, 'window_version' => $row->object_version] as $key => $value) {
                    if (!isset($params[$key])) {
                        $missing[$key] = $value;
                    }
                }
            }
        }
        if (count($rest) === count($segments) && empty($missing)) {
            return null;
        }
        $query = implode('&', $rest);
        if (!empty($missing)) {
            $query .= ($query === '' ? '' : '&') . http_build_query($missing, '', '&');
        }
        $node->setAttribute($attribute, $base . '?' . $query);
        return $doc->saveHTML($node);
    }
}
