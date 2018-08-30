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
    public const AWS_SNS_TOPIC_ARN = '_scripted_aws_sns_topic_arn';

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
     * Configures the settings page.
     *
     * @return void
     */
    public static function configure()
    {
        add_menu_page(
            'Scripted Settings',
            'Scripted.com',
            Config::REQUIRED_CAPABILITY,
            static::SLUG,
            [static::class, 'render'],
            Config::getIconUrl(),
            83
        );

        // Testing out the post published webhook.
        // array_map(function ($postId) {
        //     $post = get_post($postId);
        //     JobTasks::sendPostPublishedEvent($post->ID, $post);
        // }, [32, 85, 1]);
    }

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
            Config::setAwsSnsTopicArn(WordPressApi::getInput(static::AWS_SNS_TOPIC_ARN));
            Config::setOrgKey(WordPressApi::getInput(static::BUSINESS_ID_INPUT_NAME));
            try {
                $result = Http::curlRequest('business_user', Http::GET);
                if (WordPressApi::getInput(static::ADVANCED_SETTINGS_INPUT_NAME)) {
                    $out[] = '<div class="notice notice-success" id="message"><p>Settings updated.</p></div>';
                } else {
                    $out[] = '<script type="text/javascript">';
                    $out[] = 'window.location = "'. admin_url('/admin.php?page=scripted_jobs&auth=true') .'";';
                    $out[] = '</script>';
                }
            } catch (AccessTokenIsUnauthorized $e) {
                $out[] = '<div class="notice notice-error" id="message"><p>Sorry, we found an error. Please confirm your Organization Key and Access Token are correct and try again.</p></div>';
            }
        }

        $orgKey = Config::getOrgKey();
        $accessToken = Config::getAccessToken();
        $awsAccessKey = Config::getAwsAccessKey();
        $awsAccessSecret = Config::getAwsAccessSecret();
        $awsPostPublishTopicArn = Config::getAwsSnsTopicArn();
        $displayAdvancedSettings = (bool) WordPressApi::getInput(static::ADVANCED_SETTINGS_INPUT_NAME);

        $out[] = '<div class="wrap">';
        $out[] = '<div style="width:150px;margin:50px 0px 30px 0px;" id="icon-scripted"><img src="'.Config::getLogoUrl().'"></div>';
        $out[] = '<h2>Settings</h2>';
        $out[] = '<p>Authentication is required to use your Scripted WordPress plugin.</p>';
        $out[] = '<p>To get your Organization Key and Access Token, please register or log in at Scripted.com and then <a href="https://www.scripted.com/business/account/api" target="_blank">click here</a>. Your private authentication credentials will be available there. Copy and paste them into the settings below!</p>';
        $out[] = '<form action="" method="post" name="scripted_settings">'. wp_nonce_field(static::UPDATE_ACTION, '_wpnonce');

        $out[] = '<table class="form-table">';
        $out[] = '<tbody>';
        $out[] = '<tr valign="top">';
        $out[] = '<th scope="row"><label for="'.static::BUSINESS_ID_INPUT_NAME.'">Organization Key</label></th>';
        $out[] = '<td><input type="text" class="regular-text" value="'.$orgKey.'" name="'.static::BUSINESS_ID_INPUT_NAME.'"></td>';
        $out[] = '</tr>';
        $out[] = '<tr valign="top">';
        $out[] = '<th scope="row"><label for="'.static::ACCESS_TOKEN_INPUT_NAME.'">Access Token</label></th>';
        $out[] = '<td><textarea rows="5" class="regular-text" name="'.static::ACCESS_TOKEN_INPUT_NAME.'">'.$accessToken.'</textarea></td>';
        $out[] = '</tr>';
        $out[] = '</tbody>';
        $out[] = '</table>';

        if ($displayAdvancedSettings) {
            $out[] = '<h2>Advanced Settings</h2>';
            $out[] = '<p>Scripted.com uses AWS SNS notifications to report when posts are moved into the published state. Notifications are only sent when the following criteria are met:</p>';
            $out[] = '<ul>';
            $out[] = '<li>&bull; AWS Access Key, Access Secret, and SNS Topic ARN are configured</li>';
            $out[] = '<li>&bull; Valid Organization Key is configured</li>';
            $out[] = '<li>&bull; Published post was imported via Scripted.com plugin</li>';
            $out[] = '</ul>';
            $out[] = '<table class="form-table">';
            $out[] = '<tbody>';
            $out[] = '<tr valign="top">';
            $out[] = '<th scope="row"><label for="'.static::AWS_ACCESS_KEY_INPUT_NAME.'">AWS Access Key</label></th>';
            $out[] = '<td><input type="text" class="regular-text" value="'.$awsAccessKey.'" name="'.static::AWS_ACCESS_KEY_INPUT_NAME.'"></td>';
            $out[] = '</tr>';
            $out[] = '<tr valign="top">';
            $out[] = '<th scope="row"><label for="'.static::AWS_ACCESS_SECRET_INPUT_NAME.'">AWS Access Secret</label></th>';
            $out[] = '<td><input type="text" class="regular-text" value="'.$awsAccessSecret.'" name="'.static::AWS_ACCESS_SECRET_INPUT_NAME.'"></td>';
            $out[] = '</tr>';
            $out[] = '<tr valign="top">';
            $out[] = '<th scope="row"><label for="'.static::AWS_SNS_TOPIC_ARN.'">AWS SNS Topic ARN</label></th>';
            $out[] = '<td><input type="text" class="regular-text" value="'.$awsPostPublishTopicArn.'" name="'.static::AWS_SNS_TOPIC_ARN.'"></td>';
            $out[] = '</tr>';
            $out[] = '</tbody>';
            $out[] = '</table>';
        }

        $out[] = '<p class="submit">';
        $out[] = '<input type="submit" value="Save Changes" class="button-primary" id="submit" name="submit">';
        $out[] = '<a style="display: block; width: 10px;" href="'.$_SERVER['REQUEST_URI'].'&'.static::ADVANCED_SETTINGS_INPUT_NAME.'=true">.</a>';
        $out[] = '</p>';
        $out[] = '</form>';
        $out[] = '</div>';

        echo implode('', array_map('trim', $out));
    }
}
