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
        if (!get_option(static::ACCESS_TOKEN_KEY)) {
            add_option(static::ACCESS_TOKEN_KEY, '', '', 'no');
        }
        if (!get_option(static::BUSINESS_ID_KEY)) {
            add_option(static::BUSINESS_ID_KEY, '', '', 'no');
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
            delete_option($legacyKey);
        }, static::$legacyOptionKeys);
    }

    /**
     * Removes plugin options.
     *
     * @return void
     */
    public static function deactivatePlugin()
    {
         delete_option(static::ACCESS_TOKEN_KEY);
         delete_option(static::BUSINESS_ID_KEY);
    }

    /**
     * Fetches currently configured access token;
     *
     * @return string|null
     */
    public static function getAccessToken()
    {
        return get_option(static::ACCESS_TOKEN_KEY, null);
    }

    /**
     * Creates and returns a properly formatted wp_ajax_ action.
     *
     * @return string
     */
    public static function getAjaxAction($action)
    {
        return sprintf('wp_ajax_%s', (string) $action);
    }

    /**
     * Attempts to return the current WordPress user.
     *
     * @return WP_User
     */
    public static function getCurrentUser()
    {
        global $current_user;

        return $current_user;
    }

    /**
     * Fetches specific key from input.
     *
     * @return string|null
     */
    public static function getInput($key)
    {
        if (isset($_REQUEST) && isset($_REQUEST[(string) $key])) {
            return sanitize_text_field($_REQUEST[(string) $key]);
        }

        return null;
    }

    /**
     * Fetches currently configured organization key;
     *
     * @return string|null
     */
    public static function getOrgKey()
    {
        return get_option(static::BUSINESS_ID_KEY, null);
    }

    /**
     * Creates and returns current icon url.
     *
     * @return string
     */
    public static function getIconUrl()
    {
        return plugins_url('assets/images/favicon.ico', dirname(__FILE__));
    }

    /**
     * Creates and returns current logo url.
     *
     * @return string
     */
    public static function getLogoUrl()
    {
        return plugins_url('assets/images/logo.png', dirname(__FILE__));
    }

    /**
     * Attempts to find all post ids that are associated with a given scripted
     * project id.
     *
     * @param  string $projectId
     *
     * @return array
     */
    public static function getPostIdsByProjectId($projectId)
    {
        global $wpdb;

        $query = [];
        $query[] = "select post_id from $wpdb->postmeta";
        $query[] = "where meta_key = '".JobsPage::PROJECT_ID_META_KEY."'";
        $query[] = "and";
        $query[] = "meta_value = '$projectId'";

        $postIds = array_map(function ($result) {
            return $result['post_id'];
        }, $wpdb->get_results(implode(' ', $query), ARRAY_A));

        sort($postIds);

        return $postIds;
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
        update_option(Config::ACCESS_TOKEN_KEY, sanitize_text_field((string) $accessToken));
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
        update_option(Config::BUSINESS_ID_KEY, sanitize_text_field((string) $orgKey));
    }
}
