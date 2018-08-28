<?php

namespace Scripted;

/**
 *
 */
class Config
{
    /**
     * WordPress option key that identifies where the access token will be
     * stored in the database.
     *
     * @var string
     */
    public const ACCESS_TOKEN_KEY = '_scripted_api_key';

    /**
     * Base url for Scripted API.
     *
     * @var string
     */
    public const BASE_API_URL = 'https://api.scripted.com';

    /**
     * Base url for Scripted web app.
     *
     * @var string
     */
    public const BASE_APP_URL = 'https://app.scripted.com';

    /**
     * WordPress option key that identifies where the business id will be
     * stored in the database.
     *
     * @var string
     */
    public const BUSINESS_ID_KEY = '_scripted_business_id';

    /**
     * WordPress cache group key used to isolate cache keys used by this plugin.
     *
     * @var string
     */
    public const CACHE_GROUP = '_scripted_';

    /**
     * List of old keys from previous versions of the plugin.
     *
     * @var array
     */
    public static $legacyOptionKeys = [
        '_scripted_ID', '_scripted_auccess_tokent'
    ];

    /**
     * Initializes the plugin.
     *
     * @return void
     */
    public static function init()
    {
        static::bootstrap();
    }

    /**
     * Ensures the plugin options are available.
     *
     * @return void
     */
    public static function activatePlugin()
    {
        if (!WordPressApi::getOption(static::ACCESS_TOKEN_KEY)) {
            WordPressApi::addOption(static::ACCESS_TOKEN_KEY, '', '', 'no');
        }
        if (!WordPressApi::getOption(static::BUSINESS_ID_KEY)) {
            WordPressApi::addOption(static::BUSINESS_ID_KEY, '', '', 'no');
        }
    }

    /**
     * Initializes and organizes the plugin configuration and settings.
     *
     * @return void
     */
    protected static function bootstrap()
    {
        // Let's cleanup some old options keys.
        array_map(function ($legacyKey) {
            WordPressApi::removeOption($legacyKey);
        }, static::$legacyOptionKeys);
    }

    /**
     * Removes plugin options.
     *
     * @return void
     */
    public static function deactivatePlugin()
    {
         WordPressApi::removeOption(static::ACCESS_TOKEN_KEY);
         WordPressApi::removeOption(static::BUSINESS_ID_KEY);
    }

    /**
     * Fetches currently configured access token;
     *
     * @return string|null
     */
    public static function getAccessToken()
    {
        return WordPressApi::getOption(static::ACCESS_TOKEN_KEY, null);
    }

    /**
     * Fetches currently configured organization key;
     *
     * @return string|null
     */
    public static function getOrgKey()
    {
        return WordPressApi::getOption(static::BUSINESS_ID_KEY, null);
    }

    /**
     * Creates and returns current icon url.
     *
     * @return string
     */
    public static function getIconUrl()
    {
        return plugins_url('assets/images/favicon-16x16.png', dirname(__FILE__));
    }

    /**
     * Creates and returns current logo url.
     *
     * @return string
     */
    public static function getLogoUrl()
    {
        return plugins_url('assets/images/scripted-horizontal-dark.svg', dirname(__FILE__));
    }

    /**
     * Creates and returns current stylesheet url.
     *
     * @return string
     */
    public static function getStylesheetUrl()
    {
        return plugins_url('assets/styles/scripted.css', dirname(__FILE__));
    }

    /**
     * [log description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public static function log($data)
    {
        fwrite(fopen('php://stdout', 'w'), (string) $data);
    }

    /**
     * Updates currently configured access token;
     *
     * @param string $accessToken
     *
     * @return void
     */
    public static function setAccessToken($accessToken = null)
    {
        WordPressApi::setOption(Config::ACCESS_TOKEN_KEY, sanitize_text_field((string) $accessToken));
    }

    /**
     * Updates currently configured organization key;
     *
     * @param string $orgKey
     *
     * @return void
     */
    public static function setOrgKey($orgKey = null)
    {
        WordPressApi::setOption(Config::BUSINESS_ID_KEY, sanitize_text_field((string) $orgKey));
    }
}
