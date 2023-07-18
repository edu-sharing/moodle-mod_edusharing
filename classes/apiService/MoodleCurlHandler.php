<?php declare(strict_types = 1);

namespace mod_edusharing\apiService;

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
        $curl    = new curl();
        $params  = '';
        $options = [];
        $post    = false;
        foreach ($curlOptions as $key => $value) {
            if ($key === 'CURLOPT_HTTPHEADER') {
                $curl->header = $value;
            } else if ($key === 'CURLOPT_POSTFIELDS') {
                $params = $value;
            } else if ($key === 'CURLOPT_POST' && $value === 1) {
                $this->method = static::METHOD_POST;
            } else {
                $options[$key] = $value;
            }
        }
        if ($this->method === static::METHOD_POST) {
            $result = $curl->post($url, $params, $options);
        } elseif ($this->method === static::METHOD_PUT) {
            $result = $curl->put($url, $params, $options);
        } else {
            $result = $curl->get($url, $params, $options);
        }
        return new CurlResult($result, $curl->errno, ['message' => $curl->error]);
    }
}
