<?php

namespace Scripted;

use Aws\Sns\SnsClient;
use Exception;
use Scripted\Exceptions\AccessTokenIsUnauthorized;
use Scripted\Tools\ContentFormatter;
use stdClass;
use WP_Post;

/**
 *
 */
class JobTasks
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
     * Ajax event that triggers preview of finished job.
     *
     * @var string
     */
    public const AJAX_REFRESH_PROJECT_POST = 'scripted_refresh_project_post';

    /**
     * Post published action.
     *
     * @var string
     */
    public const POST_PUBLISHED_ACTION = 'publish_post';

    /**
     * Attempts to fetch a given project from the Scripted API, then coerce the
     * data into a WP_Post object before returning the post.
     *
     * @param  string $projectId
     * @param  integer|null $postId
     *
     * @return WP_Post|null
     */
    protected static function getProjectAsWordPressPost($projectId, $postId = null)
    {
        $currentUser = WordPressApi::getCurrentUser();

        if (!$currentUser) {
            wp_die('It does not appear that you are logged into WordPress', 401);
        }

        try {
            $projectJob = Http::curlRequest('jobs/'.$projectId, Http::GET);
            $projectContent = Http::curlRequest('jobs/'.$projectId.'/html_contents', Http::GET);

            if (!empty($projectJob) && !empty($content = $projectContent->html_contents)) {
                if (is_array($content)) {
                    $content = array_shift($content);
                }

                $postId = $postId ?: array_shift(WordPressApi::getPostIdsByProjectIds([$projectId]));
                $post = new WP_Post(new stdClass());
                if ($postId) {
                    $result = get_post($postId);
                    if (is_array($result)) {
                        $result = array_shift($result);
                    }
                    if (is_a($result, 'WP_Post')) {
                        $post = $result;
                    }
                }
                $post->post_title = ContentFormatter::trimQuotes($projectJob->topic);
                $post->post_author = $currentUser->ID;
                $post->post_type = 'post';
                $post = Tools\ContentFormatter::setPostContent($post, $content);

                return $post;
            }

            return null;
        } catch (AccessTokenIsUnauthorized $e) {
            wp_die('Scripted.com access token is not authorized.', 401);
        }
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
        $projectId = WordPressApi::getInput('projectId');
        $isPublished = (bool) WordPressApi::getInput('isPublished');

        $post = static::getProjectAsWordPressPost($projectId);

        if ($post) {
            $post->post_status = $isPublished ? 'publish' : 'draft';
            if ($post->ID) {
                $postId = wp_update_post($post, true);
            } else {
                $postId = wp_insert_post($post, true);
            }
            if (!add_post_meta($postId, Config::PROJECT_ID_META_KEY, $projectId, true)) {
                update_post_meta($postId, Config::PROJECT_ID_META_KEY, $projectId);
            }
            $postEditUrl = sprintf(
                '%s&action=edit&post=%s',
                wp_nonce_url(admin_url('post.php'), 'edit'),
                $postId
            );
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
        $projectId = WordPressApi::getInput('projectId');

        $post = static::getProjectAsWordPressPost($projectId);

        if ($post) {
            wp_die($post->post_content, 200);
        }

        wp_die('Unable to preview project', 400);
    }

    /**
     * Attempts to fetch the HTML contents of a given scripted project. If found
     * the HTML attached to the given post and the post url is returned.
     * Otherwise, an error response is sent.
     *
     * @return void
     */
    public static function renderProjectPostRefreshUrl()
    {
        $postId = WordPressApi::getInput('postId');
        $projectId = WordPressApi::getInput('projectId');

        $post = static::getProjectAsWordPressPost($projectId, $postId);

        if ($post) {
            $postId = wp_update_post($post, true);
            $postEditUrl = sprintf(
                '%s&action=edit&post=%s',
                wp_nonce_url(admin_url('post.php'), 'edit'),
                $postId
            );
            wp_die($postEditUrl, 200);
        }

        wp_die('Unable to refresh post', 400);
    }

    public static function sendPostPublishedEvent($postId, $post)
    {
        $awsAccessKey = Config::getAwsAccessKey();
        $awsAccessSecret = Config::getAwsAccessSecret();
        $awsPostPublishTopicArn = Config::getAwsSnsTopicArn();

        if ($post->ID) {
            $post->scripted_org_key = Config::getOrgKey();
            $post->scripted_job_id = get_post_meta($postId, 'scripted_project_id', true);
            $post->permalink = get_permalink($post);
            $canPushToSns = (bool) (
                $post->scripted_job_id
                && $post->scripted_org_key
                && $awsAccessKey
                && $awsAccessSecret
                && $awsPostPublishTopicArn
            );
            if ($canPushToSns) {
                try {
                    $client = SnsClient::factory(array(
                        'credentials' => [
                            'key' => $awsAccessKey,
                            'secret' => $awsAccessSecret,
                        ],
                        'region'  => 'us-east-1',
                        'version' => 'latest'
                    ));
                    $result = $client->publish(array(
                        'TopicArn' => $awsPostPublishTopicArn,
                        'Message' => json_encode($post),
                    ));
                } catch (Exception $e) {
                    Config::log($e->getMessage());
                    Config::log($e->getTraceAsString());
                }
            }
        }
    }
}
