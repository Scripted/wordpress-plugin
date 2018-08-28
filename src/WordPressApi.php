<?php

namespace Scripted;

/**
 *
 */
class WordPressApi
{
    /**
     * Adds WordPress option by key.
     *
     * @param  string $option
     * @param  mixed  $value
     * @param  string  $deprecated
     * @param  string  $autoload
     * @return boolean
     */
    public static function addOption($option, $value = '', $deprecated = '', $autoload = 'yes')
    {
        return add_option($option, $value, $deprecated, $autoload);
    }

    /**
     * Creates and returns a properly formatted wp_ajax_ action.
     *
     * @return string
     */
    public static function getAjaxAction($action)
    {
        return sprintf('wp_ajax_%s', (string) $action);
    }

    /**
     * Attempts to get a value from the WordPress cache.
     *
     * @param string  $key
     * @param mixed   $default
     *
     * @return mixed
     */
    public static function getCache($key, $default = null)
    {
        return wp_cache_get($key, Config::CACHE_GROUP) ?: $default;
    }

    /**
     * Attempts to return the current WordPress user.
     *
     * @return WP_User
     */
    public static function getCurrentUser()
    {
        global $current_user;

        return $current_user;
    }

    /**
     * Fetches specific key from input.
     *
     * @return string|null
     */
    public static function getInput($key)
    {
        if (isset($_REQUEST) && isset($_REQUEST[(string) $key])) {
            return sanitize_text_field($_REQUEST[(string) $key]);
        }

        return null;
    }

    /**
     * Fetches WordPress option by key.
     *
     * @param  string $option
     * @param  mixed  $default
     * @return mixed
     */
    public static function getOption($option, $default = null)
    {
        return get_option($option, $default);
    }

    /**
     * Attempts to find all post ids that are associated with a given scripted
     * project id.
     *
     * @param  string|array $projectIds
     *
     * @return array
     */
    public static function getPostIdsByProjectIds($projectIds)
    {
        global $wpdb;

        if (!is_array($projectIds)) {
            $projectIds = [$projectIds];
        }

        $projectIdCsv = implode(',', array_map(function ($projectId) {
            return "'$projectId'";
        }, $projectIds));

        $query = [];
        $query[] = "select post_id, meta_value from $wpdb->postmeta";
        $query[] = "where meta_key = '".JobsPage::PROJECT_ID_META_KEY."'";
        $query[] = "and";
        $query[] = "meta_value in($projectIdCsv)";

        $sql = implode(' ', $query);

        // Config::log($sql);

        $postIds = [];

        array_walk($wpdb->get_results($sql, ARRAY_A), function ($result) use (&$postIds) {
            if (!isset($postIds[$result['meta_value']])) {
                $postIds[$result['meta_value']] = [];
            }
            array_push($postIds[$result['meta_value']], $result['post_id']);
        });

        return $postIds;
    }

    /**
     * [importAndReplaceContentImages description]
     * @param  [type] $content [description]
     * @return [type]          [description]
     */
    public static function importAndReplaceContentImages($content)
    {
        $content = (string) $content;

        preg_match_all('/<img[^>]+>/i', $content, $originalImageTags);

        $imageTagReplacements = array_map(function ($imageTag) {
            preg_match('/src="([^\"]+)"/', $imageTag, $imageTagSrc);
            $originalImageTagSrc = $imageTagSrc[1];

            $parts = parse_url($originalImageTagSrc);
            $fileName = str_replace('/', '_', $parts['path']);

            $uploadDir = wp_upload_dir();
            $uploadPath = $uploadDir['path'] . '/' . $fileName;
            $imageBody = file_get_contents($originalImageTagSrc);
            $saveFile = fopen($uploadPath, 'w');
            fwrite($saveFile, $imageBody);
            fclose($saveFile);

            $wp_filetype = wp_check_filetype(basename($uploadPath), null);

            $attachment = get_page_by_title($fileName, OBJECT, 'attachment');

            if (is_null($attachment)) {
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => $fileName,
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attachmentId = wp_insert_attachment($attachment, $uploadPath);
            } else {
                $attachmentId = $attachment->ID;
            }

            $newImage = get_post($attachmentId);
            $newImageFullsizePath = get_attached_file($newImage->ID);
            $attachmentData = wp_generate_attachment_metadata($attachmentId, $newImageFullsizePath);
            wp_update_attachment_metadata($attachmentId, $attachmentData);
            $newImageFullsizeUrl = wp_get_attachment_image_src($newImage->ID, 'fullsize');

            return [
                'original' => $imageTag,
                'new' => str_replace($originalImageTagSrc, $newImageFullsizeUrl[0], $imageTag)
            ];
        }, $originalImageTags[0]);

        array_walk($imageTagReplacements, function ($replacement) use (&$content) {
            $content = str_replace($replacement['original'], $replacement['new'], $content);
        });

        return $content;
    }

    /**
     * Removes WordPress option by key.
     *
     * @param  string $option
     * @return mixed
     */
    public static function removeOption($option)
    {
        return delete_option($option);
    }

    /**
     * Attempts to set a value in the WordPress cache.
     *
     * @param string  $key
     * @param mixed  $value
     * @param integer $expire
     *
     * @return boolean
     */
    public static function setCache($key, $value, $expire = 0)
    {
        return wp_cache_set((string) $key, $value, Config::CACHE_GROUP, $expire);
    }

    /**
     * Sets WordPress option by key.
     *
     * @param  string $option
     * @param  mixed  $default
     * @return mixed
     */
    public static function setOption($option, $value)
    {
        return update_option($option, $value);
    }
}
