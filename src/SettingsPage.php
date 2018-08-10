<?php

namespace Scripted;

/**
 *
 */
class SettingsPage
{
    /**
     * Settings menu slug, used to identify the menu in the admin.
     *
     * @var string
     */
    public const SLUG = 'scripted_settings';

    /**
     * Business id form input name.
     *
     * @var string
     */
    public const BUSINESS_ID_INPUT_NAME = '_scripted_org_key';

    /**
     * Access token form input name.
     *
     * @var string
     */
    public const ACCESS_TOKEN_INPUT_NAME = '_scripted_access_token';

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
            'add_users',
            static::SLUG,
            [static::class, 'render'],
            Config::getIconUrl(),
            83
        );
    }

    public static function render()
    {
      $out = [];

      if (isset($_POST) && wp_verify_nonce($_POST['_wpnonce'], 'scriptedFormAuthSettings')) {

            $validate = Http::isAccessTokenValid(
              $_POST[static::BUSINESS_ID_INPUT_NAME],
              $_POST[static::ACCESS_TOKEN_INPUT_NAME]
            );

            if ($validate) {
                Config::setOrgKey($_POST[static::BUSINESS_ID_INPUT_NAME]);
                Config::setAccessToken($_POST[static::ACCESS_TOKEN_INPUT_NAME]);
                $out[] = '<script type="text/javascript">';
                $out[] = 'window.location = "'. admin_url('/admin.php?page=scripted_jobs&auth=true') .'";';
                $out[] = '</script>';
            } else {
                $out[] = '<div class="notice notice-error" id="message"><p>Sorry, we found an error. Please confirm your Organization Key and Access Token are correct and try again.</p></div>';
            }
        }

        $orgKey = Config::getOrgKey();
        $accessToken = Config::getAccessToken();

        $out[] = '<div class="wrap">';
        $out[] = '<div style="width:100px;margin-top:5px;" id="icon-scripted"><img src="'.Config::getLogoUrl().'"></div>';
        $out[] = '<h2>Settings</h2>';
        $out[] = '<p>Authentication is required to use your Scripted WordPress plugin.</p>';
        $out[] = '<p>To get your Organization Key and Access Token, please register or log in at Scripted.com and then <a href="https://www.scripted.com/business/account/api" target="_blank">click here</a>. Your private authentication credentials will be available there. Copy and paste them into the settings below!</p>';
        $out[] = '<form action="" method="post" name="scripted_settings">'. wp_nonce_field( 'scriptedFormAuthSettings', '_wpnonce' );


        $out[] = '<table class="form-table">
          <tbody>
          <tr valign="top">
          <th scope="row"><label for="'.static::BUSINESS_ID_INPUT_NAME.'">Organization Key</label></th>
          <td><input type="text" class="regular-text" value="'.$orgKey.'" name="'.static::BUSINESS_ID_INPUT_NAME.'"></td>
          </tr>
          <tr valign="top">
          <th scope="row"><label for="'.static::ACCESS_TOKEN_INPUT_NAME.'">Access Token</label></th>
          <td><textarea rows="5" class="regular-text" name="'.static::ACCESS_TOKEN_INPUT_NAME.'">'.$accessToken.'</textarea></td>
          </tr>
          </tbody>
          </table>
          <p class="submit">
          <input type="submit" value="Save Changes" class="button-primary" id="submit" name="submit">
          </p>';
        $out[] = '</form>';
        $out[] = '</div>';

        echo implode('', array_map('trim', $out));
    }
}
