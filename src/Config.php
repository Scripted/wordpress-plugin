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
     * WordPress option key that identifies where the aws access key will be
     * stored in the database.
     *
     * @var string
     */
    public const AWS_ACCESS_KEY_KEY = '_scripted_aws_access_key';

    /**
     * WordPress option key that identifies where the aws access secret will be
     * stored in the database.
     *
     * @var string
     */
    public const AWS_ACCESS_SECRET_KEY = '_scripted_aws_access_secret';

    /**
     * WordPress option key that identifies where the aws sns topic arn will be
     * stored in the database.
     *
     * @var string
     */
    public const AWS_SNS_TOPIC_ARN_KEY = '_scripted_aws_sns_topic_arn';

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
     * Meta data key used to store project id on post.
     *
     * @var string
     */
    public const PROJECT_ID_META_KEY = 'scripted_project_id';

    /**
     * WordPress capability required to administer plugin.
     *
     * @var string
     */
    public const REQUIRED_CAPABILITY = 'manage_options';

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
     * Fetches currently configured aws access key;
     *
     * @return string|null
     */
    public static function getAwsAccessKey()
    {
        return WordPressApi::getOption(static::AWS_ACCESS_KEY_KEY, null);
    }

    /**
     * Fetches currently configured aws access secret;
     *
     * @return string|null
     */
    public static function getAwsAccessSecret()
    {
        return WordPressApi::getOption(static::AWS_ACCESS_SECRET_KEY, null);
    }

    /**
     * Fetches currently configured aws sns topic arn;
     *
     * @return string|null
     */
    public static function getAwsSnsTopicArn()
    {
        return WordPressApi::getOption(static::AWS_SNS_TOPIC_ARN_KEY, null);
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
        return WordPressApi::getPluginUrlFor('assets/images/favicon-16x16.png');
    }

    /**
     * Creates and returns current logo url.
     *
     * @return string
     */
    public static function getLogoUrl()
    {
        return WordPressApi::getPluginUrlFor('assets/images/scripted-horizontal-dark.svg');
    }

    /**
     * Creates and returns current stylesheet url.
     *
     * @return string
     */
    public static function getStylesheetUrl()
    {
        return WordPressApi::getPluginUrlFor('assets/styles/scripted.css');
    }

    /**
     * [log description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public static function log($data)
    {
        fwrite(
            fopen('php://stdout', 'w'),
            "\n\n" . (string) $data . "\n\n"
        );
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
     * Updates currently configured aws access key;
     *
     * @param string $awsAccessKey
     *
     * @return void
     */
    public static function setAwsAccessKey($awsAccessKey)
    {
        WordPressApi::setOption(static::AWS_ACCESS_KEY_KEY, sanitize_text_field((string) $awsAccessKey));
    }

    /**
     * Updates currently configured aws access key;
     *
     * @param string $awsAccessSecret
     *
     * @return void
     */
    public static function setAwsAccessSecret($awsAccessSecret)
    {
        WordPressApi::setOption(static::AWS_ACCESS_SECRET_KEY, sanitize_text_field((string) $awsAccessSecret));
    }

    /**
     * Updates currently configured aws sns topic arn;
     *
     * @param string $awsSnsTopicArn
     *
     * @return void
     */
    public static function setAwsSnsTopicArn($awsSnsTopicArn)
    {
        WordPressApi::setOption(static::AWS_SNS_TOPIC_ARN_KEY, sanitize_text_field((string) $awsSnsTopicArn));
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
