<?php

namespace Scripted;

/**
 *
 */
class Notice
{
    /**
     * Attempts to display any relevant notices to the current user.
     *
     * @return string|null
     */
    public static function render()
    {
        return implode('', array_map('trim', [
            static::renderInitialConfigConfirmation(),
            static::renderInstallWarning()
        ]));
    }

    /**
     * Builds an admin dialog notification.
     *
     * @param  string  $message
     * @param  boolean $error
     *
     * @return string
     */
    public static function renderAdminDialog($message, $error = false)
    {
        $options = [];
        if ($error) {
            $options['failure'] = $message;
            $options['id'] = 'scripted_warning';
        } else {
            $options['success'] = $message;
            $options['id'] = 'scripted_notification';
        }

        return View::render('partials.notification', $options);
    }

    /**
     * Builds an admin-facing warning if the current user hasn't configured
     * authentication settings.
     *
     * @return string|null
     */
    protected static function renderInstallWarning()
    {
        $orgKey = Config::getOrgKey();
        $accessToken = Config::getAccessToken();
        $page = (isset($_GET['page']) ? $_GET['page'] : null);

        if ((empty($orgKey) || empty($accessToken)) && $page != SettingsPage::SLUG && current_user_can(Config::REQUIRED_CAPABILITY)) {
            return static::renderAdminDialog(
                sprintf(
                    'You must %sconfigure the plugin%s to enable Scripted for WordPress.',
                    '<a href="admin.php?page='.SettingsPage::SLUG.'">',
                    '</a>'
                ),
                true
            );
        }

        return null;
    }

    /**
     * Builds an admin-facing confirmation notification about successful auth set up.
     *
     * @return string|null
     */
    protected static function renderInitialConfigConfirmation()
    {
        if (WordPressApi::getInput('auth')) {
            return static::renderAdminDialog('Great! Your code validation is correct. Thanks, enjoy...');
        }

        return null;
    }
}
