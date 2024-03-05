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

use curl;
use EduSharingApiClient\CurlHandler;
use EduSharingApiClient\CurlResult;

/**
 * class MoodleCurlHandler
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 */
class MoodleCurlHandler extends CurlHandler {
    /**
     * Function handleCurlRequest
     *
     * Method name does not comply with moodle code style
     * in order to ensure compatibility with edu-sharing api library
     *
     * @param string $url
     * @param array $curlOptions
     * @return CurlResult
     */
    // phpcs:ignore -- Function cannot be lowercase as it implements an interface
    public function handleCurlRequest(string $url, array $curlOptions): CurlResult {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl         = new curl();
        $params       = [];
        $options      = [];
        $allconstants = null;
        // phpcs:ignore -- var name is camelCase to match interface.
        foreach ($curlOptions as $key => $value) {
            if (is_int($key)) {
                if ($allconstants === null) {
                    $allconstants = get_defined_constants(true)['curl'];
                }
                $key = array_search($key, $allconstants, true);
                if ($key === false) {
                    continue;
                }
            }
            if ($key === 'CURLOPT_HTTPHEADER') {
                $curl->header = $value;
            } else if ($key === 'CURLOPT_POSTFIELDS') {
                $params = $value;
            } else if ($key === 'CURLOPT_POST' && $value === 1) {
                $this->method = static::METHOD_POST;
            } else if ($key === 'CURLOPT_CUSTOMREQUEST' && $value === 'DELETE') {
                $this->method = static::METHOD_DELETE;
            } else {
                $options[$key] = $value;
            }
        }
        if ($this->method === static::METHOD_POST) {
            $result = $curl->post($url, $params, $options);
        } else if ($this->method === static::METHOD_PUT) {
            $result = $curl->put($url, $params, $options);
        } else if ($this->method === static::METHOD_DELETE) {
            $result = $curl->delete($url, $params, $options);
        } else {
            $result = $curl->get($url, $params, $options);
        }
        if ($curl->errno !== 0 && is_array($curl->info)) {
            $curl->info['message'] = $curl->error;
        }
        $this->method = self::METHOD_GET;
        return new CurlResult($result, $curl->errno, is_array($curl->info) ? $curl->info : ['message' => $curl->error]);
    }
}
