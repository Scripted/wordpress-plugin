<?php

namespace Scripted;

/**
 *
 */
class Http
{
    /**
     * Default cache length in seconds.
     *
     * @var integer
     */
    public const DEFAULT_CACHE_LENGTH_SECONDS = 600;

    /**
     * Attempts to make an HTTP request with the given parameters.
     *
     * @param  string  $type
     * @param  boolean $post
     * @param  string  $fields
     *
     * @return mixed
     */
    public static function curlRequest($type, $post = false, $fields = '')
    {
        $orgKey = Config::getOrgKey();
        $accessToken = Config::getAccessToken();
        $url = sprintf(
            '%s/%s/v1/%s',
            Config::BASE_API_URL,
            $orgKey,
            $type
        );

        Config::log($url);

        $cachedResults = WordPressApi::getCache($url);

        if ($cachedResults) {
            return $cachedResults;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken));
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ((bool) $post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        } else {
            curl_setopt($ch, CURLOPT_POST, 0);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === false) {
            return false;
        }

        list($header, $contents) = preg_split( '/([\r\n][\r\n])\\1/', $result, 2 );
        if ($contents != '') {
            $contents = json_decode($contents);
            if (isset($contents->data) && count($contents->data) > 0) {
                if(isset($contents->total_count)) {
                    WordPressApi::setCache($url, $contents, static::DEFAULT_CACHE_LENGTH_SECONDS);
                    return $contents;
                }
                WordPressApi::setCache($url, $contents->data, static::DEFAULT_CACHE_LENGTH_SECONDS);
                return $contents->data;
            }
        }

        return false;
    }

    /**
     * Attempts to request a benign resource with the given organization key and
     * access token as a means of determining whether the credentials are still
     * authorized.
     *
     * @param  string  $orgKey
     * @param  string  $accessToken
     *
     * @return boolean
     */
    public static function isAccessTokenValid($orgKey, $accessToken)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken));
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_URL, Config::BASE_API_URL.'/'.$orgKey.'/v1/industries/');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === false) {
            return false;
        }

        list($header, $contents) = preg_split( '/([\r\n][\r\n])\\1/', $result, 2 );
        $industries = json_decode($contents);
        if ($contents != '') {
            if (isset($industries->data) && count($industries->data) > 0) {
                return true;
            }
        }

        return false;
    }
}
