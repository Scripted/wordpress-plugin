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
     * @return void
     */
    public static function render()
    {
        static::renderInstallWarning();
    }

    /**
     * Echos an admin dialog notification.
     *
     * @param  string  $message
     * @param  boolean $error
     *
     * @return void
     */
    public static function renderAdminDialog($message, $error = false)
    {
        $id = $error ? 'scripted_warning' : 'scripted_notification';
        $class = $error ? 'error' : 'updated';

        echo sprintf('<div id="%s" class="%s fade"><p>%s</p></div>', $id, $class, $message);
    }

    /**
     * Display an admin-facing warning if the current user hasn't configured
     * authentication settings.
     *
     * @return void
     */
    protected static function renderInstallWarning()
    {
        $orgKey = Config::getOrgKey();
        $accessToken = Config::getAccessToken();
        $page = (isset($_GET['page']) ? $_GET['page'] : null);

        if ((empty($orgKey) || empty($accessToken)) && $page != SettingsPage::SLUG && current_user_can('manage_options')) {
            static::renderAdminDialog(
                sprintf(
                    'You must %sconfigure the plugin%s to enable Scripted for WordPress.',
                    '<a href="admin.php?page='.SettingsPage::SLUG.'">',
                    '</a>'
                ),
                true
            );
        }
    }
}
