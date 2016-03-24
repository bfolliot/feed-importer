<?php
/**
 * Feed Importer
 *
 * @link      https://github.com/bfolliot/feed-importer for the canonical source repository
 * @copyright Copyright (c) 2016 Bryan Folliot. (https://bryanfolliot.fr)
 * @license   New BSD License
 */

namespace BFolliot\FeedImporter;

use WP_Query;
use Zend\Feed\Reader\Entry\EntryInterface;
use Zend\Feed\Reader\Reader;
use InvalidArgumentException;

class Importer implements ImporterInterface
{

    /**
     * { @inheritdoc }
     */
    public function import($uri, $postType = 'post')
    {
        if (empty($uri) || !is_string($uri)) {
            throw new InvalidArgumentException('Uri is required.');
        }
        if (!post_type_exists($postType)) {
            throw new InvalidArgumentException(
                sprintf('%s is not a valid post type.', $postType)
            );
        }

        $feed = Reader::import($uri);

        foreach ($feed as $entry) {
            if ($this->entryIsNew($entry, $postType)) {
                $this->createPostFromEntry($entry, $postType);
            }
        }
    }

    /**
     * Check if entry is new.
     *
     * @param EntryInterface $entry
     * @param  string $postType The post type name, default to "post"
     *
     * @return bool
     */
    private function entryIsNew(EntryInterface $entry, $postType = 'post')
    {
        $queryArgs = [
            'post_type'   => $postType,
            'post_status' => 'any',
            'meta_query'  => [
                [
                    'key'     => 'feed_importer_feed_entry_id',
                    'value'   => $entry->getId(),
                ],
            ],
        ];

        $query = new WP_Query($queryArgs);
        return ($query->post_count == 0);
    }

    /**
     * Create post from entry
     *
     * @param EntryInterface $entry
     * @param  string $postType The post type name, default to "post"
     */
    private function createPostFromEntry(EntryInterface $entry, $postType = 'post')
    {
        $date = (!empty($entry->getDateModified()))
            ? $entry->getDateModified()->format('Y-m-d H:i:s')
            : null;

        // Create post object
        $postData = [
          'post_author'  => 1,
          'post_date'    => $date,
          'post_content' => wp_kses_post($entry->getContent()),
          'post_title'   => wp_strip_all_tags($entry->getTitle()),
          'post_type'    => $postType,
        ];

        // Insert the post into the database
        $postId = wp_insert_post($postData);

        // Add meta.
        if (is_int($postId)) {
            add_post_meta($postId, 'feed_importer_feed_entry_id', $entry->getId());
            if ($entry->getLink()) {
                add_post_meta($postId, 'feed_importer_feed_entry_link', $entry->getLink());
            }
            if ($entry->getAuthors()) {
                add_post_meta($postId, 'feed_importer_feed_entry_link', $entry->getAuthors()->getValues());
            }
            if (function_exists('pll_set_post_language')) {
                pll_set_post_language($postId, 'en');
            }
        }
    }
}
