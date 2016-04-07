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

class Importer implements ImporterInterface
{

    /**
     * @var string
     */
    protected $feedUri;

    /**
     * @var string
     */
    protected $postType = 'post';

    /**
     * @var array
     */
    protected $entryParams = [
        'afterInsertPost' => null,
        'afterInsertTerm' => null,
        'taxonomy'        => null,
        'authorId'        => 1,
    ];

    /**
     * __construct
     *
     * @param (string) $uri The URI to the feed (optional)
     *
     * @return self
     */
    public function __construct($feedUri = null)
    {
        $this->setFeedUri($feedUri);

        return $this;
    }

    /**
     * @inheritDoc
     *
     */
    public function import()
    {
        if (empty($this->feedUri) || !is_string($this->feedUri)) {
            throw new Exception\UnexpectedValueException(sprintf(
                'Feed uri not valid.'
            ));
        }
        $feed = Reader::import($this->feedUri);
        foreach ($feed as $entry) {
            if ($this->entryIsNew($entry)) {
                $postId = $this->createPostFromEntry($entry);
                if (!empty($this->entryParams['taxonomy'])) {
                    $this->createTermsFromEntry($postId, $entry);
                }
            }
        }
    }

    /**
     * Check if entry is new.
     *
     * @param EntryInterface $entry
     *
     * @return bool
     */
    protected function entryIsNew(EntryInterface $entry)
    {
        $query = new WP_Query([
            'post_type'   => $this->postType,
            'post_status' => 'any',
            'meta_query'  => [
                [
                    'key'     => 'feed_importer_feed_entry_id',
                    'value'   => $entry->getId(),
                ],
            ],
        ]);
        return ($query->post_count == 0);
    }

    /**
     * Create post from entry
     *
     * @param EntryInterface $entry
     *
     * @return int|WP_Error The post ID on success. The value 0 or WP_Error on failure.
     */
    protected function createPostFromEntry(EntryInterface $entry)
    {
        $date = (!empty($entry->getDateModified()))
            ? $entry->getDateModified()->format('Y-m-d H:i:s')
            : null;

        // Create post object
        $postData = [
          'post_author'  => $this->entryParams['authorId'],
          'post_content' => wp_kses_post($entry->getContent()),
          'post_date'    => $date,
          'post_title'   => wp_strip_all_tags($entry->getTitle()),
          'post_type'    => $this->postType,
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

            if (is_callable($this->entryParams['afterInsertPost'])) {
                call_user_func($this->entryParams['afterInsertPost'], $postId);
            }
        }

        return $postId;
    }

    /**
     * Create taxonomy terms from entry
     *
     * @param integer $postId
     * @param EntryInterface $entry
     *
     * @return array of term ids
     */
    protected function createTermsFromEntry($postId, EntryInterface $entry)
    {
        $ids = [];
        foreach ($entry->getCategories() as $category) {
            $term = term_exists($category['term'], $this->entryParams['taxonomy']);

            if ($term === 0 || $term === null) {
                $term = wp_insert_term($category['term'], $this->entryParams['taxonomy']);
                if (is_array($term)) {
                    if (is_callable($this->entryParams['afterInsertTerm'])) {
                        call_user_func($this->entryParams['afterInsertTerm'], $term['term_id']);
                    }
                }
            }

            $ids[] = (int) $term['term_id'];
        }
        if (!empty($ids)) {
            wp_set_post_terms($postId, $ids, $this->entryParams['taxonomy']);
        }
    }

    /**
     * @inheritDoc
     *
     */
    public function configureEntry($postType = 'post', $params = [])
    {
        if (!post_type_exists($postType)) {
            throw new Exception\UnexpectedValueException(sprintf(
                '%s post type does not exist.'
            ));
        }
        $this->postType = $postType;

        if (!empty($params)) {
            if (!empty($params['afterInsertPost']) && !is_callable($params['afterInsertPost'])) {
                throw new Exception\InvalidArgumentException(
                    '"afterInsertPost" parameter is not a callable.'
                );
            }
            if (!empty($params['afterInsertTerm']) && !is_callable($params['afterInsertTerm'])) {
                throw new Exception\InvalidArgumentException(
                    '"afterInsertTerm" parameter is not a callable.'
                );
            }
            if (!empty($params['taxonomy']) && !taxonomy_exists($params['taxonomy'])) {
                throw new Exception\UnexpectedValueException(sprintf(
                    '%s taxonomy does not exist.',
                    $params['taxonomy']
                ));
            }
            if (!empty($params['authorId']) && !is_int($params['authorId'])) {
                throw new Exception\InvalidArgumentException(
                    '"authorId" parameter is not an integer.'
                );
            }
            $this->entryParams = array_merge(
                $this->entryParams,
                $params
            );
        }

        return $this;
    }

    /**
     * @inheritDoc
     *
     */
    public function setFeedUri($feedUri)
    {
        $this->feedUri = $feedUri;

        return $this;
    }
}
