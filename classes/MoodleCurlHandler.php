<?php declare(strict_types=1);

namespace mod_edusharing;

use curl;
use EduSharingApiClient\CurlHandler;
use EduSharingApiClient\CurlResult;

/**
 * class MoodleCurlHandler
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
class MoodleCurlHandler extends CurlHandler
{
    /**
     * Function handleCurlRequest
     *
     * @param string $url
     * @param array $curlOptions
     * @return CurlResult
     */
    public function handleCurlRequest(string $url, array $curlOptions): CurlResult {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl         = new curl();
        $params       = [];
        $options      = [];
        $allConstants = null;
        foreach ($curlOptions as $key => $value) {
            if (is_int($key)) {
                if ($allConstants === null) {
                    $allConstants = get_defined_constants(true)['curl'];
                }
                $key = array_search($key, $allConstants, true);
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
        } elseif ($this->method === static::METHOD_PUT) {
            $result = $curl->put($url, $params, $options);
        } elseif ($this->method === static::METHOD_DELETE) {
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
