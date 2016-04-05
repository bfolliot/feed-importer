<?php
/**
 * Feed Importer
 *
 * @link      https://github.com/bfolliot/feed-importer for the canonical source repository
 * @copyright Copyright (c) 2016 Bryan Folliot. (https://bryanfolliot.fr)
 * @license   New BSD License
 */

namespace BFolliot\FeedImporter;

interface ImporterInterface
{
    /**
     * Lunch import.
     *
     * @param  string $uri The URI to the feed
     * @param  string $postType The post type name, default to "post"
     * @param  string $taxonomyType Optional taxonomy type name to match with feed category
     * @param  string $language Optionnal Polylang language
     *
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function import($uri, $postType = 'post', $taxonomyType = null, $language = false);
}
