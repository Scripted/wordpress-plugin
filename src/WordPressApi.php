<?php

namespace Scripted;

use Exception;
use stdClass;

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
     *
     * @return boolean
     */
    public static function addOption($option, $value = '', $deprecated = '', $autoload = 'yes')
    {
        return add_option($option, $value, $deprecated, $autoload);
    }

    /**
     * Attempts to clear all items from WordPress cache.
     *
     * @return bool
     */
    public static function clearCache()
    {
        return wp_cache_flush();
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
     *
     * @return mixed
     */
    public static function getOption($option, $default = null)
    {
        return get_option($option, $default);
    }

    /**
     * Attempts to get plugin url for a given path.
     *
     * @param  string $path
     *
     * @return string
     */
    public static function getPluginUrlFor($path)
    {
        return plugins_url((string) $path, dirname(__FILE__));
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
        $query[] = "where meta_key = '".Config::PROJECT_ID_META_KEY."'";
        $query[] = "and";
        $query[] = "meta_value in($projectIdCsv)";

        $sql = implode(' ', $query);

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
     * Attempts to replace all inline images of a given html body with WordPress
     * media attachments.
     *
     * @param  string $content
     *
     * @return string
     */
    public static function importAndReplaceContentImages($content)
    {
        try {
            // Let's make sure we are dealing with a string (hopefully HTML)
            $content = (string) $content;

            // Let's find all occurences of img tags
            preg_match_all('/<img[^>]+>/i', $content, $originalImageTags);

            // Let's create an array of replacement rules
            $imageTagReplacements = array_map(function ($imageTag) {
                // Let's locate the first occurence of the src attribute
                // on the given image tag
                preg_match('/src="([^\"]+)"/', $imageTag, $imageTagSrc);

                // Let's return a null replacement rule if we did not locate a
                // src attribute
                if (!isset($imageTagSrc[1])) {
                    return null;
                }
                $originalImageTagSrc = $imageTagSrc[1];

                // Let's create a unique filename from the original image src
                $parts = parse_url($originalImageTagSrc);
                $fileName = str_replace('/', '_', $parts['path']);

                // Let's check for an existing attachment in the media library
                // based on the computed filename
                $attachment = get_page_by_title($fileName, OBJECT, 'attachment');

                // Let's create a new attachment if not found
                if (is_null($attachment)) {
                    // Let's download the contents of the original image file, then
                    // upload to the appropriate directory.
                    $uploadDir = wp_upload_dir();
                    $uploadPath = $uploadDir['path'] . '/' . $fileName;
                    $imageBody = file_get_contents($originalImageTagSrc);
                    $saveFile = fopen($uploadPath, 'w');
                    fwrite($saveFile, $imageBody);
                    fclose($saveFile);

                    // Let's determine the file type of the original image
                    $imageFileType = wp_check_filetype(basename($uploadPath), null);

                    $attachment = array(
                        'post_mime_type' => $imageFileType['type'],
                        'post_title' => $fileName,
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    $attachmentId = wp_insert_attachment($attachment, $uploadPath);
                } else {
                    $attachmentId = $attachment->ID;
                }

                // Let's get the attachment post object
                $newImage = get_post($attachmentId);
                // Let's get the attachment fullsize path
                $newImageFullsizePath = get_attached_file($newImage->ID);
                // Let's get the attachment meta data
                $attachmentData = wp_generate_attachment_metadata($attachmentId, $newImageFullsizePath);
                // Let's update the attachment meta data
                wp_update_attachment_metadata($attachmentId, $attachmentData);
                // Let's get the attachment fullsize url
                $newImageFullsizeUrl = wp_get_attachment_image_src($newImage->ID, 'fullsize');

                // Let's return a set of replacement instructions
                $replacement = new stdClass();
                $replacement->original = $imageTag;
                $replacement->new = str_replace($originalImageTagSrc, $newImageFullsizeUrl[0], $imageTag);

                return $replacement;
            }, $originalImageTags[0]);

            // Let's remove any of those null replacements from missing src attributes
            $imageTagReplacements = array_filter($imageTagReplacements);

            // Let's loop over our replacement rules and perform the replacements
            array_walk($imageTagReplacements, function ($replacement) use (&$content) {
                $content = str_replace($replacement->original, $replacement->new, $content);
            });
        } catch (Exception $e) {
            // Not terribly sure what we should do here...
        }

        // Let's return our (hopefully modified) HTML content
        return $content;
    }

    /**
     * Removes WordPress option by key.
     *
     * @param  string $option
     *
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
     *
     * @return mixed
     */
    public static function setOption($option, $value)
    {
        return update_option($option, $value);
    }
}
