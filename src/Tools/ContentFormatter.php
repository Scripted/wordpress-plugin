<?php

namespace Scripted\Tools;

use Scripted\WordPressApi;
use WP_Post;

class ContentFormatter
{
    /**
     * [setPostContent description]
     * @param WP_Post $post    [description]
     * @param [type]  $content [description]
     */
    public static function setPostContent(WP_Post $post, $content)
    {
        $post->post_content = WordPressApi::importAndReplaceContentImages($content);

        return $post;
    }
}
