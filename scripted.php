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
require_once(dirname( __FILE__ ) . '/aws.phar');
require_once(dirname( __FILE__ ) . '/twig.phar');
require_once(dirname( __FILE__ ) . '/src/Config.php');
require_once(dirname( __FILE__ ) . '/src/Exceptions/AccessTokenIsUnauthorized.php');
require_once(dirname( __FILE__ ) . '/src/Http.php');
require_once(dirname( __FILE__ ) . '/src/JobsPage.php');
require_once(dirname( __FILE__ ) . '/src/JobTasks.php');
require_once(dirname( __FILE__ ) . '/src/Jobs/Filter.php');
require_once(dirname( __FILE__ ) . '/src/Notice.php');
require_once(dirname( __FILE__ ) . '/src/SettingsPage.php');
require_once(dirname( __FILE__ ) . '/src/Tools/ContentFormatter.php');
require_once(dirname( __FILE__ ) . '/src/View.php');
require_once(dirname( __FILE__ ) . '/src/WordPressApi.php');

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
    function () {
        // // Testing out the post published webhook.
        // array_map(function ($postId) {
        //     $post = get_post($postId);
        //     Scripted\JobTasks::sendPostPublishedEvent($post->ID, $post);
        // }, [32, 85, 1]);
        add_menu_page(
            'Scripted Settings',
            'Scripted.com',
            Scripted\Config::REQUIRED_CAPABILITY,
            Scripted\SettingsPage::SLUG,
            function () {
                echo call_user_func_array(
                    [Scripted\SettingsPage::class, 'render'],
                    func_get_args()
                );
            },
            Scripted\Config::getIconUrl(),
            83
        );
    }
);

// Let's add our jobs menu to the admin navigation.
add_action(
    'admin_menu',
    // [Scripted\JobsPage::class, 'configure']
    function () {
        if (Scripted\Config::canConnectToPlatform()) {
            $currentJobPageSlug = add_submenu_page(
                Scripted\SettingsPage::SLUG,
                'Jobs',
                'Jobs',
                Scripted\Config::REQUIRED_CAPABILITY,
                Scripted\JobsPage::SLUG,
                function () {
                    echo call_user_func_array(
                        [Scripted\JobsPage::class, 'render'],
                        func_get_args()
                    );
                }
            );

            add_action(
                sprintf('admin_footer-%s', $currentJobPageSlug),
                function () {
                    echo call_user_func_array(
                        [Scripted\JobsPage::class, 'renderAsyncJobManagementJavascript'],
                        func_get_args()
                    );
                }
            );

            add_action(
                sprintf('admin_print_styles-%s', $currentJobPageSlug),
                function () {
                    $adminStyleName = 'scriptedAdminStyle';
                    wp_register_style($adminStyleName, Scripted\Config::getStylesheetUrl());
                    wp_enqueue_style($adminStyleName);
                }
            );
        }
    }
);

// Let's publish notifications to the current user, if needed.
add_action(
    'admin_notices',
    function () {
        echo call_user_func_array(
            [Scripted\Notice::class, 'render'],
            func_get_args()
        );
    }
);

// Let's tell WordPress how to handle ajax requests for project previews.
add_action(
    Scripted\WordPressApi::getAjaxAction(Scripted\JobTasks::AJAX_FINISHED_JOB_PREVIEW),
    [Scripted\JobTasks::class, 'renderFinishedJobPreview']
);

// Let's tell WordPress how to handle ajax requests for converting a project into a post.
add_action(
    Scripted\WordPressApi::getAjaxAction(Scripted\JobTasks::AJAX_CREATE_PROJECT_DRAFT),
    [Scripted\JobTasks::class, 'renderProjectPostEditUrl']
);

// Let's tell WordPress how to handle ajax requests for refreshing a project post.
add_action(
    Scripted\WordPressApi::getAjaxAction(Scripted\JobTasks::AJAX_REFRESH_PROJECT_POST),
    [Scripted\JobTasks::class, 'renderProjectPostRefreshUrl']
);

// Let's tell WordPress how to handle post publish events for Scripted Jobs.
// AWS Access Key and Secret must be configured for this action to be effective.
// Only Posts with a Scripted Job ID meta data value set will be affected.
add_action(
    Scripted\JobTasks::POST_PUBLISHED_ACTION,
    [Scripted\JobTasks::class, 'sendPostPublishedEvent'],
    10, 2
);
