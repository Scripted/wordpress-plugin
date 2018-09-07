<?php

namespace Scripted;

use Scripted\Exceptions\AccessTokenIsUnauthorized;

/**
 *
 */
class SettingsPage
{
    /**
     * Access token form input name.
     *
     * @var string
     */
    public const ACCESS_TOKEN_INPUT_NAME = '_scripted_access_token';

    /**
     * Advanced settings input name.
     *
     * @var string
     */
    public const ADVANCED_SETTINGS_INPUT_NAME = '_scripted_advanced_settings';

    /**
     * AWS access key form input name.
     *
     * @var string
     */
    public const AWS_ACCESS_KEY_INPUT_NAME = '_scripted_aws_access_key';

    /**
     * AWS access secret form input name.
     *
     * @var string
     */
    public const AWS_ACCESS_SECRET_INPUT_NAME = '_scripted_aws_access_secret';

    /**
     * AWS SNS topic arn form input name.
     *
     * @var string
     */
    public const AWS_SNS_TOPIC_ARN_INPUT_NAME = '_scripted_aws_sns_topic_arn';

    /**
     * Business id form input name.
     *
     * @var string
     */
    public const BUSINESS_ID_INPUT_NAME = '_scripted_org_key';

    /**
     * Settings menu slug, used to identify the menu in the admin.
     *
     * @var string
     */
    public const SLUG = 'scripted_settings';

    /**
     * Action slug for form processing action.
     *
     * @var string
     */
    public const UPDATE_ACTION = '_scripted_form_auth_settings';

    /**
     * Renders settings page markup.
     *
     * @return void
     */
    public static function render()
    {
        $out = [];

        if (wp_verify_nonce(WordPressApi::getInput('_wpnonce'), static::UPDATE_ACTION)) {
            Config::setAccessToken(WordPressApi::getInput(static::ACCESS_TOKEN_INPUT_NAME));
            Config::setAwsAccessKey(WordPressApi::getInput(static::AWS_ACCESS_KEY_INPUT_NAME));
            Config::setAwsAccessSecret(WordPressApi::getInput(static::AWS_ACCESS_SECRET_INPUT_NAME));
            Config::setAwsSnsTopicArn(WordPressApi::getInput(static::AWS_SNS_TOPIC_ARN_INPUT_NAME));
            Config::setOrgKey(WordPressApi::getInput(static::BUSINESS_ID_INPUT_NAME));
            try {
                $result = Http::getResponse('business_user', Http::GET);
                if (WordPressApi::getInput(static::ADVANCED_SETTINGS_INPUT_NAME)) {
                    $out[] = View::render('partials.notification', ['success' => 'Settings updated.']);
                } else {
                    $out[] = View::render('partials.inline-redirect', ['target' => admin_url('/admin.php?page=scripted_jobs&auth=true')]);
                }
            } catch (AccessTokenIsUnauthorized $e) {
                $out[] = View::render('partials.notification', ['failure' => 'Sorry, we found an error. Please confirm your Organization Key and Access Token are correct and try again.']);
            }
        }

        $pageData = [
            'formName' => static::SLUG,
            'orgKey' => Config::getOrgKey(),
            'orgKeyInputName' => static::BUSINESS_ID_INPUT_NAME,
            'accessToken' => Config::getAccessToken(),
            'accessTokenInputName' => static::ACCESS_TOKEN_INPUT_NAME,
            'awsAccessKey' => Config::getAwsAccessKey(),
            'awsAccessKeyInputName' => static::AWS_ACCESS_KEY_INPUT_NAME,
            'awsAccessSecret' => Config::getAwsAccessSecret(),
            'awsAccessSecretInputName' => static::AWS_ACCESS_SECRET_INPUT_NAME,
            'awsPostPublishTopicArn' => Config::getAwsSnsTopicArn(),
            'awsPostPublishTopicArnInputName' => static::AWS_SNS_TOPIC_ARN_INPUT_NAME,
            'displayAdvancedSettings' => (bool) WordPressApi::getInput(static::ADVANCED_SETTINGS_INPUT_NAME),
            'formNonce' => wp_nonce_field(static::UPDATE_ACTION, '_wpnonce'),
            'advancedSettingsUrl' => sprintf('%s&%s=true', $_SERVER['REQUEST_URI'], static::ADVANCED_SETTINGS_INPUT_NAME),
        ];

        $out[] = View::render('settings', $pageData);

        return implode('', array_map('trim', $out));
    }
}
