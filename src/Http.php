<?php

namespace Scripted;

use Scripted\Exceptions\AccessTokenIsUnauthorized;

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
     * Http get verb.
     *
     * @var string
     */
    public const GET = 'GET';

    /**
     * Http post verb.
     *
     * @var string
     */
    public const POST = 'POST';


    /**
     * Attempts to make an HTTP request with the given parameters.
     *
     * @param  string  $path
     * @param  string $verb
     * @param  array  $config
     *
     * @return mixed
     */
    public static function curlRequest($path, $verb, array $config = array())
    {
        $orgKey = static::getArrayDefault($config['orgKey'], Config::getOrgKey());
        $accessToken = static::getArrayDefault($config['accessToken'], Config::getAccessToken());

        if (!$orgKey || !$accessToken) {
            throw new AccessTokenIsUnauthorized();
        }

        $clearCache = (bool) static::getArrayDefault($config['clearCache']);
        $url = sprintf(
            '%s/%s/v1/%s',
            Config::BASE_API_URL,
            $orgKey,
            $path
        );
        $cacheKey = sprintf('%s::%s::%s', $orgKey, $accessToken, $url);

        Config::log($cacheKey);

        if ($clearCache) {
            WordPressApi::setCache($cacheKey, null);
        }

        $cachedResults = WordPressApi::getCache($cacheKey);

        if ($cachedResults) {
            return $cachedResults;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken));
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($verb == static::POST) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        } else {
            curl_setopt($ch, CURLOPT_POST, 0);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode >= 400) {
            throw new AccessTokenIsUnauthorized();
        }

        if ($result) {
            list($header, $contents) = preg_split('/([\r\n][\r\n])\\1/', $result, 2);
            if ($contents != '') {
                $contents = json_decode($contents);
                if (isset($contents->data) && count($contents->data) > 0) {
                    if(isset($contents->total_count)) {
                        WordPressApi::setCache($cacheKey, $contents, static::DEFAULT_CACHE_LENGTH_SECONDS);
                        return $contents;
                    }
                    WordPressApi::setCache($cacheKey, $contents->data, static::DEFAULT_CACHE_LENGTH_SECONDS);
                    return $contents->data;
                }
            }
        }

        return null;
    }

    /**
     * Attempts to return the value of var, otherwise returns default.
     *
     * @param  mixed &$var
     * @param  mixed $default
     *
     * @return mixed
     */
    protected static function getArrayDefault(&$var, $default = null)
    {
        return isset($var) ? $var : $default;
    }
}
