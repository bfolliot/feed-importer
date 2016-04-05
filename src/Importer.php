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
     * @var callable
     */
    private $postInsertCallback;

    /**
     * @var callable
     */
    private $termInsertCallback;

    /**
     * { @inheritdoc }
     */
    public function import($uri, $postType = 'post', $taxonomyType = null, $language = false)
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
            if ($this->entryIsNew($entry, $postType, $language)) {
                $postId = $this->createPostFromEntry($entry, $postType, $language);
                if (!empty($taxonomyType)) {
                    $this->createTermsFromEntry($postId, $entry, $taxonomyType, $language);
                }
            }
        }
    }

    /**
     * Check if entry is new.
     *
     * @param EntryInterface $entry
     * @param  string $postType The post type name, default to "post"
     * @param  string $language Optionnal Polylang language
     *
     * @return bool
     */
    private function entryIsNew(EntryInterface $entry, $postType = 'post', $language = false)
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

        if ($language) {
            $queryArgs['lang'] = $language;
        }

        $query = new WP_Query($queryArgs);
        return ($query->post_count == 0);
    }

    /**
     * Create post from entry
     * If you set a callable with setPostInsertCallback(),
     * he will be triggered after insert with postId as parameter.
     *
     * @param EntryInterface $entry
     * @param  string $postType The post type name, default to "post"
     * @param  string $language Optionnal Polylang language
     */
    private function createPostFromEntry(EntryInterface $entry, $postType = 'post', $language = false)
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

            if ($language && function_exists('pll_set_post_language')) {
                pll_set_post_language($postId, $language);
            }

            if (is_callable($this->postInsertCallback)) {
                call_user_func($this->postInsertCallback, $postId);
            }
        }

        return $postId;
    }

    /**
     * Create taxonomy terms from entry
     *
     * @param integer $postId
     * @param EntryInterface $entry
     * @param string $taxonomyType The taxonomy type name
     * @param  string $language Optionnal Polylang language
     */
    private function createTermsFromEntry($postId, EntryInterface $entry, $taxonomyType, $language = false)
    {
        $ids = [];
        foreach ($entry->getCategories() as $category) {
            $term = term_exists($category['term'], $taxonomyType);

            if ($term === 0 || $term === null) {
                $term = wp_insert_term($category['term'], $taxonomyType);
                if (is_array($term)) {
                    if ($language && function_exists('pll_set_term_language')) {
                        pll_set_term_language($postId, $language);
                    }
                    if (is_callable($this->termInsertCallback)) {
                        call_user_func($this->termInsertCallback, $term['term_id']);
                    }
                }
            }

            $ids[] = (int) $term['term_id'];
        }
        if (!empty($ids)) {
            wp_set_post_terms($postId, $ids, $taxonomyType);
        }
    }

    /**
     * Sets the value of postInsertCallback.
     *
     * @param callable $postInsertCallback the post insert callback
     *
     * @return self
     */
    public function setPostInsertCallback(callable $postInsertCallback)
    {
        $this->postInsertCallback = $postInsertCallback;

        return $this;
    }

    /**
     * Sets the value of termInsertCallback.
     *
     * @param callable $termInsertCallback the term insert callback
     *
     * @return self
     */
    public function setTermInsertCallback(callable $termInsertCallback)
    {
        $this->termInsertCallback = $termInsertCallback;

        return $this;
    }
}
