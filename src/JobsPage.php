<?php

namespace Scripted;

/**
 *
 */
class JobsPage
{

    /**
     * Ajax event that triggers preview of finished job.
     *
     * @var string
     */
    public const AJAX_CREATE_PROJECT_DRAFT = 'scripted_create_project_draft';

    /**
     * Ajax event that triggers preview of finished job.
     *
     * @var string
     */
    public const AJAX_FINISHED_JOB_PREVIEW = 'scripted_preview_finished_job';

    /**
     * Meta data key used to store project id on post.
     *
     * @var string
     */
    public const PROJECT_ID_META_KEY = 'scripted_project_id';

    /**
     * Settings menu slug, used to identify the menu in the admin.
     *
     * @var string
     */
    public const SLUG = 'scripted_jobs';


    /**
     * Configures the settings page.
     *
     * @return void
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
            'manage_options',
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

        $orgKey = Config::getOrgKey();
        $accessToken = Config::getAccessToken();
        $paged = (isset($_GET['paged']) && $_GET['paged'] !='') ? sanitize_text_field($_GET['paged']) : '';
        $perPage = 15;
        $validate = Http::isAccessTokenValid($orgKey,$accessToken);
        $out = ['<div class="wrap">'];

        if ( $_GET['auth'] )
            $out[] = '<div class="notice notice-success" id="message"><p>Great! Your code validation is correct. Thanks, enjoy...</p></div>';

        $out[] = '<div style="width:100px;margin-top:5px;" id="icon-scripted"><img src="'.Config::getLogoUrl().'"></div><h2>Jobs</h2>';

        $filter = (!isset($_GET['filter'])) ? 'all' : sanitize_text_field($_GET['filter']);
        $jobUrl = ($filter !='all') ? 'jobs/'.$filter : 'jobs/';

        if($validate) {
            $url = ($paged != '') ? $jobUrl.'?next_cursor='.$paged : $jobUrl;
            $result = Http::curlRequest($url);
            $allJobs = $result->data;

            $next = (isset($result->paging->has_next) && $result->paging->has_next == 1) ? $result->paging->next_cursor : '';
            $totalProjects  = $result->total_count;
            $totalPages     = ceil($totalProjects/$perPage);

            // pagination

            $pagination = '';

            $pageOne = '';
            if ($paged == '' && $result->paging->has_next != 1) {
                $pageOne = ' one-page';
            }

             $pagination .='<div class="tablenav">
                 <div class="alignleft actions bulkactions">
                    <select class="filter-jobs" name="action">
                        <option '.selected('all',$filter,false).' value="">All</option>
                        <option '.selected('accepted',$filter,false).' value="accepted">Accepted</option>
                        <option '.selected('finished',$filter,false).' value="finished">Finished</option>
              <option '.selected('screening',$filter,false).' value="screening">Screening</option>
              <option '.selected('writing',$filter,false).' value="writing">Writing</option>
              <option '.selected('draft_ready',$filter,false).' value="draft_ready">Draft Ready</option>
              <option '.selected('revising',$filter,false).' value="revising">Revising</option>
              <option '.selected('final_ready',$filter,false).' value="final_ready">Final Ready</option>
              <option '.selected('in_progress',$filter,false).' value="in_progress">In Progress</option>
              <option '.selected('needs_review',$filter,false).' value="needs_review">Needs Review</option>
                    </select>
                </div>
                <div class="tablenav-pages'.$pageOne.'">';

                    $pagination .='';
                    $nextPage = '';
                    if($result->paging->has_next != 1)
                        $nextPage = 'disabled';

                    $pagination .='<span class="pagination-links">
                                <span class="displaying-num">'.$totalProjects.' items</span>
                                <a href="admin.php?page=scripted_jobs&paged='.$next.'&filter='.$filter.'" title="Go to the next page" class="next-page '.$nextPage.'">&rsaquo;</a>';

                       $pagination .='</span>
                 </div>
                <br class="clear">
                </div>';
            // pagination end

            $out[] = $pagination;

            $out[] ='<table cellspacing="0" class="wp-list-table widefat sTable">
                        <thead>
                            <tr>
                            <th scope="col" width="50%"><span>Topic</span></th>
                            <th scope="col" width="10%"><span>State</span></th>
                            <th scope="col" width="15%"><span>Deadline</span></th>
                            <th scope="col" width="23%"></th>
                            </tr>
                        </thead>
                          <tbody>';

            if ($allJobs)  {
                $i = 1;
                foreach($allJobs as $job) {
                    $out[] ='<tr valign="top" class="scripted type-page status-publish hentry alternate">
                        <input type="hidden" id="project_'.$i.'" value="'.$job->id.'">
                        <td>'.$job->topic.'</td>
                        <td>'.ucfirst($job->state).'</td>
                        <td>'.date('F j', strtotime($job->deadline_at)).'</td>';

                        $out[] ='<td>';
                        if ($job->state == 'accepted') {
                            $previewAjaxUrl = wp_nonce_url(admin_url('admin-ajax.php'), static::AJAX_FINISHED_JOB_PREVIEW ).'&action='.static::AJAX_FINISHED_JOB_PREVIEW.'&projectId='.$job->id;
                            $out[] = '<a id="create_'.$job->id.'" href="javascript:void(0);" onclick="Scripted.createProjectPost(\''.$job->id.'\', false, this)">Create Draft</a> |&nbsp';
                            $out[] = '<a id="post_'.$job->id.'" href="javascript:void(0);" onclick="Scripted.createProjectPost(\''.$job->id.'\', true, this)">Create Post</a> |&nbsp;';
                            $out[] = '<a href="'.$previewAjaxUrl.'&'.urlencode('TB_iframe=1&width=850&height=500').'" class="thickbox" title="'.strip_tags(substr($job->topic,0,50)).'">Preview</a>';
                        }
                        $out[] ='</td>';
                        $out[] ='</tr>';
                        $i++;
                }
            } else {
                $out[] ='<tr valign="top">
                        <th colspan="5"  style="text-align:center;" class="check-column"><strong>Your Scripted account has no jobs... yet!</strong></td>
                        </tr>';
            }

             $out[] = '</tbody>
                    </table>'; // end table

           $out[] = $pagination;


        }

        $out[] ='</div>';// end of wrap div

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
                        url: '<?php echo wp_nonce_url( admin_url('admin-ajax.php'), static::AJAX_CREATE_PROJECT_DRAFT);?>&isPublished='+ (isPublished ? '1' : '0') +'&projectId='+projectId+'&action=<?php echo static::AJAX_CREATE_PROJECT_DRAFT; ?>',
                        data: '',
                        success: function(data) {
                            window.location = data;
                        },
                        error: function (error) {
                            console.error(error);
                            link.text(originalText + ' (Try again?)').attr('disabled', false);
                        }
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

    /**
     * Attempts to convert a project into a post and, if successful, returns
     * the secured post edit url.
     *
     * The created post will also be associated with the scripted project id.
     * If an existing post is in the system, associated with the scripted
     * project id, that post will be used instead.
     *
     * @return void
     */
    public static function renderProjectPostEditUrl()
    {
        $projectId = Config::getInput('projectId');
        $isPublished = (bool) Config::getInput('isPublished');
        $orgKey = Config::getOrgKey();
        $accessToken = Config::getAccessToken();
        $validate = Http::isAccessTokenValid($orgKey, $accessToken);
        $currentUser = Config::getCurrentUser();

        if (!$currentUser) {
            wp_die('It does not appear that you are logged into WordPress', 401);
        }

        if (!$validate) {
            wp_die('Scripted.com access token is not authorized.', 401);
        }

        $projectJob = Http::curlRequest('jobs/'.$projectId);
        $projectContent = Http::curlRequest('jobs/'.$projectId.'/html_contents');

        if (!empty($projectJob) && !empty($content = $projectContent->html_contents)) {
            if (is_array($content)) {
                $content = $content[0];
            }

            $postIds = Config::getPostIdsByProjectId($projectId);

            $post = [];
            if (!empty($postIds)) {
                $result = get_post(array_shift($postIds));
                if (is_array($result)) {
                    $result = array_shift($result);
                }
                if (is_a($result, 'WP_Post')) {
                    $post = $result->to_array();
                }
            }
            $post['post_title'] = wp_strip_all_tags($projectJob->topic);
            $post['post_status'] = $isPublished ? 'publish' : 'draft';
            $post['post_author'] = $currentUser->ID;
            $post['post_type'] = 'post';
            $post['post_content'] = $content;
            if (isset($post['ID'])) {
                $postId = wp_update_post($post, true);
            } else {
                $postId = wp_insert_post($post, true);
            }
            if (!add_post_meta($postId, static::PROJECT_ID_META_KEY, $projectId, true)) {
                update_post_meta($postId, static::PROJECT_ID_META_KEY, $projectId);
            }
            $postEditUrl = wp_nonce_url(admin_url('post.php'), 'edit').'&action=edit&post='.$postId;
            wp_die($postEditUrl, 200);
        }

        wp_die('Unable to create draft', 400);
    }

    /**
     * Attempts to fetch the HTML contents of a given scripted project. If found
     * the HTML is returned. Otherwise, an error response is sent.
     *
     * @return void
     */
    public static function renderFinishedJobPreview()
    {
        $projectId = Config::getInput('projectId');
        $orgKey = Config::getOrgKey();
        $accessToken = Config::getAccessToken();
        $validate = Http::isAccessTokenValid($orgKey, $accessToken);

        if (!$validate) {
            wp_die('Scripted.com access token is not authorized.', 401);
        }

        $projectContent = Http::curlRequest('jobs/'.$projectId.'/html_contents');

        if (!empty($content = $projectContent->html_contents)) {
            if(is_array($content)) {
                $content = $content[0];
            }
            wp_die($content, 200);
        }

        wp_die('Unable to preview project', 400);
    }
}
