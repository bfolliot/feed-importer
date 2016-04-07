<?php
/**
 * Feed Importer
 *
 * @link      https://github.com/bfolliot/feed-importer for the canonical source repository
 * @copyright Copyright (c) 2016 Bryan Folliot. (https://bryanfolliot.fr)
 * @license   New BSD License
 */

namespace BFolliot\FeedImporter;

/**
 * Useful features when handling post imported by FeedImporter
 */
class Api
{

    /**
     * get the source link of the post.
     *
     * @param integer $postId
     * @param array $params
     */
    public static function getSourceLink($postId, $params = [])
    {
        $source = get_post_meta($postId, 'feed_importer_feed_entry_link', true);
        if (empty(trim($source))) {
            return;
        }
        $defaults = [
            'after'  => '</p>',
            'before' => '<p>' . __('Source:') . ' ',
            'echo'   => 1,
            'label'  => esc_url($source),
            'title'  => null,
        ];
        $params = wp_parse_args($params, $defaults);

        $output = $params['before'];
        $output .= '<a href="' . esc_url($source) . '" title="' . $params['title'] . '">';
        $output .= $params['label'];
        $output .= '</a>';
        $output .= $params['after'];

        if ($params['echo']) {
            echo $output;
        }
        return $output;
    }
}
