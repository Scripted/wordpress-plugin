<?php

namespace Scripted;

use Scripted\Exceptions\AccessTokenIsUnauthorized;

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
     * Builds an ajax preview url for the given job.
     *
     * @param  object $job
     * @return string
     */
    public static function getJobAjaxPreviewUrl($job)
    {
        return sprintf(
            '%s&action=%s&projectId=%s',
            wp_nonce_url(admin_url('admin-ajax.php'), JobTasks::AJAX_FINISHED_JOB_PREVIEW ),
            JobTasks::AJAX_FINISHED_JOB_PREVIEW,
            $job->id
        );
    }

    /**
     * Builds a list of filter objects.
     *
     * @return array
     */
    public static function getJobFilters()
    {
        $filters = [];
        $currentFilter = (string) WordPressApi::getInput('filter');
        $filterConfig = [
            'All' => '',
            'Accepted' => 'accepted',
            'Finished' => 'finished',
            'Screening' => 'screening',
            'Writing' => 'writing',
            'Draft Ready' => 'draft_ready',
            'Revising' => 'revising',
            'Final Ready' => 'final_ready',
            'In Progress' => 'in_progress',
            'Needs Review' => 'needs_review',
        ];

        array_walk($filterConfig, function ($slug, $name) use ($currentFilter, &$filters) {
            $filters[] = new Jobs\Filter($name, $slug, $slug == $currentFilter);
        });

        return $filters;
    }

    /**
     * Attempts to get the first post id from the given collection of post ids
     * associated with the given job id.
     *
     * @param  object $job
     * @param  array  $postIds
     *
     * @return string|null
     */
    public static function getPostIdForJob($job, array $postIds)
    {
        if (isset($postIds[$job->id])) {
            return (string) array_shift($postIds[$job->id]);
        }

        return null;
    }

    /**
     * Fetches all the jobs based on the current query, then renders a list view
     * in the WordPress admin screen.
     *
     * @return string
     */
    public static function render()
    {
        wp_enqueue_style('thickbox');
        wp_enqueue_script('thickbox');

        try {
            $paged = WordPressApi::getInput('paged');
            $filter = WordPressApi::getInput('filter');

            $jobUrl = implode('/', array_filter(['jobs', $filter]));
            $jobUrl .= sprintf('?%s', http_build_query([
                'next_cursor' => $paged
            ]));

            $result = Http::getResponse($jobUrl, Http::GET);

            $postIds = [];

            if (is_array($result->data)) {
                $jobIds = array_map(function ($job) {
                    return $job->id;
                }, $result->data);

                $postIds = WordPressApi::getPostIdsByProjectIds($jobIds);
            }

            return View::render('jobs', [
                'paginatedJobs' => $result,
                'postIds' => $postIds,
            ]);
        } catch (AccessTokenIsUnauthorized $e) {
            return View::render('jobs');
        }
    }

    /**
     * Renders the client side Javascript onto the page to aid in the functionality
     * of the jobs list view.
     *
     * @return string
     */
    public static function renderAsyncJobManagementJavascript()
    {
        $options = [
            'createProjectPostBaseUrl' => wp_nonce_url( admin_url('admin-ajax.php'), JobTasks::AJAX_CREATE_PROJECT_DRAFT).'&action='.JobTasks::AJAX_CREATE_PROJECT_DRAFT,
            'filterJobsBaseUrl' => admin_url('admin.php?page='.static::SLUG),
            'refreshProjectPostBaseUrl' => wp_nonce_url(admin_url('admin-ajax.php'), JobTasks::AJAX_REFRESH_PROJECT_POST).'&action='.JobTasks::AJAX_REFRESH_PROJECT_POST,
        ];
        return View::render('jobs.async-job-management-js', $options);
    }
}
