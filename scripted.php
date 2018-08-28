<?php

/*
Plugin Name: Scripted.com Writing Marketplace
Plugin URI: https://wordpress.org/plugins/scripted-api
Description: Get great writers and manage your Scripted account from WordPress!
Author: Scripted.com
Version: 3.0.0
Author URI: https://www.scripted.com
*/

// Let's ensure the plugin classes are included.
require_once(dirname( __FILE__ ) . '/src/WordPressApi.php');
require_once(dirname( __FILE__ ) . '/src/Config.php');
require_once(dirname( __FILE__ ) . '/src/Http.php');
require_once(dirname( __FILE__ ) . '/src/SettingsPage.php');
require_once(dirname( __FILE__ ) . '/src/JobsPage.php');
require_once(dirname( __FILE__ ) . '/src/Notice.php');
require_once(dirname( __FILE__ ) . '/src/Tools/ContentFormatter.php');

// Let's initialize our plugin.
add_action(
    'plugins_loaded',
    [Scripted\Config::class, 'init'],
    8
);

// Let's tell WordPress what to do when the plugin is activated.
register_activation_hook(
    __FILE__,
    [Scripted\Config::class, 'activatePlugin']
);

// Let's tell WordPress what to do when the plugin is deactivated.
register_deactivation_hook(
    __FILE__,
    [Scripted\Config::class, 'deactivatePlugin']
);

// Let's add our settings menu to the admin navigation.
add_action(
    'admin_menu',
    [Scripted\SettingsPage::class, 'configure']
);

// Let's add our jobs menu to the admin navigation.
add_action(
    'admin_menu',
    [Scripted\JobsPage::class, 'configure']
);

// Let's publish notifications to the current user, if needed.
add_action(
    'admin_notices',
    [Scripted\Notice::class, 'render']
);

// Let's tell WordPress how to handle ajax requests for project previews.
add_action(
    Scripted\WordPressApi::getAjaxAction(Scripted\JobsPage::AJAX_FINISHED_JOB_PREVIEW),
    [Scripted\JobsPage::class, 'renderFinishedJobPreview']
);

// Let's tell WordPress how to handle ajax requests for converting a project into a post.
add_action(
    Scripted\WordPressApi::getAjaxAction(Scripted\JobsPage::AJAX_CREATE_PROJECT_DRAFT),
    [Scripted\JobsPage::class, 'renderProjectPostEditUrl']
);

// Let's tell WordPress how to handle ajax requests for refreshing a project post.
add_action(
    Scripted\WordPressApi::getAjaxAction(Scripted\JobsPage::AJAX_REFRESH_PROJECT_POST),
    [Scripted\JobsPage::class, 'renderProjectPostRefreshUrl']
);
