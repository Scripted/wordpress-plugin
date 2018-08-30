<?php

namespace Scripted\Tools;

use Scripted\JobTasks;
use Scripted\WordPressApi;
use WP_Post;

class ContentFormatter
{
    /**
     * [getJobGrid description]
     * @param  array  $jobs [description]
     * @return string
     */
    public static function getJobGrid(array $jobs)
    {
        $markup[] ='<table cellspacing="0" class="wp-list-table widefat sTable">';
        $markup[] ='<thead>';
        $markup[] ='<tr>';
        $markup[] ='<th scope="col" width="50%"><span>Topic</span></th>';
        $markup[] ='<th scope="col" width="10%"><span>State</span></th>';
        $markup[] ='<th scope="col" width="15%"><span>Deadline</span></th>';
        $markup[] ='<th scope="col" width="23%"></th>';
        $markup[] ='</tr>';
        $markup[] ='</thead>';
        $markup[] ='<tbody>';

        $jobIds = array_map(function ($job) {
            return $job->id;
        }, $jobs);

        $postIds = WordPressApi::getPostIdsByProjectIds($jobIds);

        foreach($jobs as $index => $job) {
            $markup[] ='<tr valign="top" class="scripted type-page status-publish hentry alternate">';
            $markup[] ='<input type="hidden" id="project_'.$index.'" value="'.$job->id.'">';
            $markup[] ='<td>'.ContentFormatter::trimQuotes($job->topic).'</td>';
            $markup[] ='<td>'.ucfirst($job->state).'</td>';
            $markup[] ='<td>'.date('F j', strtotime($job->deadline_at)).'</td>';
            $markup[] ='<td>';
            if ($job->state == 'accepted') {
                $previewAjaxUrl = wp_nonce_url(admin_url('admin-ajax.php'), JobTasks::AJAX_FINISHED_JOB_PREVIEW ).'&action='.JobTasks::AJAX_FINISHED_JOB_PREVIEW.'&projectId='.$job->id;
                if (isset($postIds[$job->id])) {
                    $postId = array_shift($postIds[$job->id]);
                    $editUrl = wp_nonce_url(admin_url('post.php'), 'edit' ).'&action=edit&post='.$postId;
                    $markup[] = '<a id="edit_'.$job->id.'" href="'.$editUrl.'">Edit Post</a> |&nbsp';
                    $markup[] = '<a id="refresh_'.$job->id.'" href="javascript:void(0);" onclick="Scripted.refreshProjectPost(\''.$job->id.'\', \''.$postId.'\', this)">Refresh Post</a> |&nbsp';
                } else {
                    $markup[] = '<a id="create_'.$job->id.'" href="javascript:void(0);" onclick="Scripted.createProjectPost(\''.$job->id.'\', false, this)">Create Draft</a> |&nbsp';
                    $markup[] = '<a id="post_'.$job->id.'" href="javascript:void(0);" onclick="Scripted.createProjectPost(\''.$job->id.'\', true, this)">Create Post</a> |&nbsp;';
                }
                $markup[] = '<a href="'.$previewAjaxUrl.'&'.urlencode('TB_iframe=1&width=850&height=500').'" class="thickbox" title="'.strip_tags(substr($job->topic,0,50)).'">Preview</a>';
            }
            $markup[] ='</td>';
            $markup[] ='</tr>';
        }

        $markup[] ='</tbody>';
        $markup[] ='</table>';

        return implode('', array_map('trim', $markup));
    }

    public static function getJobPagination($paginatedResponse)
    {
        $filter = WordPressApi::getInput('filter') ?: 'all';

        $markup[] = '<div class="tablenav">';
        $markup[] = '<div class="alignleft actions bulkactions">';
        $markup[] = '<select class="filter-jobs" name="action">';
        $markup[] = '<option '.selected('all', $filter, false).' value="">All</option>';
        $markup[] = '<option '.selected('accepted', $filter, false).' value="accepted">Accepted</option>';
        $markup[] = '<option '.selected('finished', $filter, false).' value="finished">Finished</option>';
        $markup[] = '<option '.selected('screening', $filter, false).' value="screening">Screening</option>';
        $markup[] = '<option '.selected('writing', $filter, false).' value="writing">Writing</option>';
        $markup[] = '<option '.selected('draft_ready', $filter, false).' value="draft_ready">Draft Ready</option>';
        $markup[] = '<option '.selected('revising', $filter, false).' value="revising">Revising</option>';
        $markup[] = '<option '.selected('final_ready', $filter, false).' value="final_ready">Final Ready</option>';
        $markup[] = '<option '.selected('in_progress', $filter, false).' value="in_progress">In Progress</option>';
        $markup[] = '<option '.selected('needs_review', $filter, false).' value="needs_review">Needs Review</option>';
        $markup[] = '</select>';
        $markup[] = '</div>';

        if ($paginatedResponse && property_exists($paginatedResponse, 'paging')) {
            $nextCursor = $paginatedResponse->paging->next_cursor;
            $totalProjects  = $paginatedResponse->total_count;
            $nextPage = $paginatedResponse->paging->has_next != 1 ? 'disabled' : '';
            $pageOne = $paginatedResponse->paging->has_next != 1 ? 'one-page' : '';

            $markup[] = '<div class="tablenav-pages'.$pageOne.'">';
            $markup[] = '<span class="pagination-links">';
            $markup[] = '<span class="displaying-num">'.$totalProjects.' items</span>';
            $markup[] = '<a href="admin.php?page=scripted_jobs&paged='.$nextCursor.'&filter='.$filter.'" title="Go to the next page" class="next-page '.$nextPage.'">&rsaquo;</a>';
            $markup[] = '</span>';
            $markup[] = '</div>';
        }

        $markup[] = '<br class="clear">';
        $markup[] = '</div>';

        return implode('', array_map('trim', $markup));
    }

    /**
     * Attempts to update the content of a given post based on Scripted business
     * logic.
     *
     * @param WP_Post $post
     * @param string  $content
     *
     * @return WP_Post
     */
    public static function setPostContent(WP_Post $post, $content)
    {
        $post->post_content = WordPressApi::importAndReplaceContentImages($content);

        return $post;
    }

    /**
     * Removes leading and trailing quotes from give string.
     *
     * @param  string $title
     *
     * @return string
     */
    public static function trimQuotes($title)
    {
        return trim(trim((string) $title, "'"), '"');
    }
}
