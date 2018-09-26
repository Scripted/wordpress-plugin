<?php

namespace Scripted;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use JmesPath;
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
    const DEFAULT_CACHE_LENGTH_SECONDS = 600;


    /**
     * Http get verb.
     *
     * @var string
     */
    const GET = 'GET';

    /**
     * Http post verb.
     *
     * @var string
     */
    const POST = 'POST';


    /**
     * Attempts to make an HTTP request with the given parameters.
     *
     * @param  string  $path
     * @param  string $verb
     * @param  array  $config
     *
     * @return mixed
     * @throws AccessTokenIsUnauthorized
     *
     * @see https://github.com/jmespath/jmespath.php
     * @see http://docs.guzzlephp.org/en/stable/
     */
    public static function getResponse($path, $verb, array $config = array())
    {
        $orgKey = JmesPath\search('orgKey', $config) ?: Config::getOrgKey();
        $accessToken = JmesPath\search('accessToken', $config) ?: Config::getAccessToken();

        if (!$orgKey || !$accessToken) {
            throw new AccessTokenIsUnauthorized();
        }

        $clearCache = (bool) JmesPath\search('clearCache', $config);
        $url = sprintf(
            '%s/%s/v1/%s',
            Config::BASE_API_URL,
            $orgKey,
            $path
        );
        $cacheKey = sprintf('%s::%s::%s', $orgKey, $accessToken, $url);

        if ($clearCache) {
            WordPressApi::setCache($cacheKey, null);
        }

        $cachedResults = WordPressApi::getCache($cacheKey);

        if ($cachedResults) {
            return $cachedResults;
        }

        $options = [
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $accessToken)
            ]
        ];

        if ($verb == static::POST) {
            $options['body'] = $fields;
        }

        try {
            $response = (new Client())->request($verb, $url, $options);
        } catch (ClientException $e) {
            throw new AccessTokenIsUnauthorized($e->getResponse()->getReasonPhrase());
        }

        $contents = (string) $response->getBody();
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            throw new AccessTokenIsUnauthorized($response->getReasonPhrase());
        }

        if (!empty($contents)) {
            $contents = json_decode($contents);
            if (isset($contents->data)) {
                if(isset($contents->total_count)) {
                    WordPressApi::setCache($cacheKey, $contents, static::DEFAULT_CACHE_LENGTH_SECONDS);
                    return $contents;
                }
                WordPressApi::setCache($cacheKey, $contents->data, static::DEFAULT_CACHE_LENGTH_SECONDS);
                return $contents->data;
            }
        }

        return null;
    }
}
