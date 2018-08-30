<?php

namespace Scripted;

use Scripted\Exceptions\AccessTokenIsUnauthorized;
use Scripted\Tools\ContentFormatter;

/**
 *
 */
class JobsPage
{
    /**
     * Settings menu slug, used to identify the menu in the admin.
     *
     * @var string
     */
    public const SLUG = 'scripted_jobs';


    /**
     * Configures the settings page.
     *
     * @return string
     */
    public static function configure()
    {
        $orgKey = Config::getOrgKey();
        $accessToken = Config::getAccessToken();

        if (empty($orgKey) || empty($accessToken)) {
            return;
        }

        $currentJobPageSlug = add_submenu_page(
            SettingsPage::SLUG,
            'Jobs',
            'Jobs',
            Config::REQUIRED_CAPABILITY,
            static::SLUG,
            [static::class, 'render']
        );

        add_action(
            sprintf('admin_footer-%s', $currentJobPageSlug),
            [static::class, 'renderAsyncJobManagementJavascript']
        );

        add_action(
            sprintf('admin_print_styles-%s', $currentJobPageSlug),
            function () {
                $adminStyleName = 'scriptedAdminStyle';
                wp_register_style($adminStyleName, Config::getStylesheetUrl());
                wp_enqueue_style($adminStyleName);
            }
        );
    }

    /**
     * Fetches all the jobs based on the current query, then renders a list view
     * in the WordPress admin screen.
     *
     * @return void
     */
    public static function render()
    {
        wp_enqueue_style('thickbox');
        wp_enqueue_script('thickbox');

        $out[] = '<div class="wrap" role="container">';

        if ( $_GET['auth'] )
            $out[] = '<div class="notice notice-success" id="message"><p>Great! Your code validation is correct. Thanks, enjoy...</p></div>';

        $out[] = '<div style="width:150px;margin:50px 0px 30px 0px;" id="icon-scripted"><img src="'.Config::getLogoUrl().'"></div>';

        $out[] = '<h2>Jobs</h2>';

        try {
            $paged = WordPressApi::getInput('paged');
            $filter = WordPressApi::getInput('filter');

            $jobUrl = implode('/', array_filter(['jobs', $filter]));
            $jobUrl .= sprintf('?%s', http_build_query([
                'next_cursor' => $paged
            ]));

            $result = Http::curlRequest($jobUrl, Http::GET);
            $pagination = ContentFormatter::getJobPagination($result);

            $out[] = $pagination;
            if (is_array($result->data)) {
                $out[] = ContentFormatter::getJobGrid($result->data);
            } else {
                $out[] = '<div class="no-data">';
                $out[] = '<h3>No jobs found</h3>';
                $out[] = '</div>';
            }
            $out[] = $pagination;
        } catch (AccessTokenIsUnauthorized $e) {
            $out[] = '<div class="no-data">';
            $out[] = '<h3>It appears as if the access token is not valid.</h3>';
            $out[] = '</div>';
        }

        $out[] ='</div>';

        echo implode('', array_map('trim', $out));
    }

    /**
     * Renders the client side Javascript onto the page to aid in the functionality
     * of the jobs list view.
     *
     * @return void
     */
    public static function renderAsyncJobManagementJavascript()
    {
        ?>
        <script>
            var Scripted = {
                createProjectPost: function (projectId, isPublished, caller) {
                    var link = jQuery(caller);
                    var originalText = link.text();
                    link.text('Creating...').attr('disabled', true);
                    jQuery.ajax({
                        type: 'POST',
                        url: '<?php echo wp_nonce_url( admin_url('admin-ajax.php'), JobTasks::AJAX_CREATE_PROJECT_DRAFT);?>&isPublished='+ (isPublished ? '1' : '0') +'&projectId='+projectId+'&action=<?php echo JobTasks::AJAX_CREATE_PROJECT_DRAFT; ?>',
                        data: '',
                        success: function(data) {
                            window.location = data;
                        },
                        error: function (error) {
                            var errorMessage = 'Failed to create project post: ' + error;
                            Scripted.showErrorMessage(errorMessage);
                            link.text(originalText).attr('disabled', false);
                        }
                    });
                },
                refreshProjectPost: function (projectId, postId, caller) {
                    var link = jQuery(caller);
                    var originalText = link.text();
                    link.text('Refreshing...').attr('disabled', true);
                    jQuery.ajax({
                        type: 'POST',
                        url: '<?php echo wp_nonce_url( admin_url('admin-ajax.php'), JobTasks::AJAX_REFRESH_PROJECT_POST);?>&projectId='+projectId+'&postId='+postId+'&action=<?php echo JobTasks::AJAX_REFRESH_PROJECT_POST; ?>',
                        data: '',
                        success: function(data) {
                            window.location = data;
                        },
                        error: function (xhr, status, error) {
                            var errorMessage = 'Failed to refresh project post: ' + error;
                            Scripted.showErrorMessage(errorMessage);
                            link.text(originalText).attr('disabled', false);
                        }
                    });
                },
                showErrorMessage: function (errorMessage) {
                    var errorBanner = jQuery('<div />').hide().addClass('notice').addClass('notice-error').append('<p>'+errorMessage+'</p>');
                    jQuery('[role="container"]').prepend(errorBanner);
                    errorBanner.slideDown(function (e) {
                        setTimeout(function () {
                            errorBanner.slideUp();
                        }, 5000);
                    });
                }
            };

            jQuery( document ).ready(function() {
                jQuery('.filter-jobs').change(function() {
                    var filter = jQuery(this).val();
                    document.location.href = '<?php echo admin_url('admin.php?page=scripted_jobs');?>&filter='+filter
                });
            });
        </script>
        <?php
    }
}
