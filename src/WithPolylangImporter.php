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

class WithPolylangImporter extends Importer
{

    /**
     * @var string
     */
    private $language = null;

    /**
     * @inheritDoc
     *
     * @param string $language
     */
    public function __construct($language, $feedUri = null)
    {
        if (!function_exists('pll_languages_list')) {
            throw new Exception\RuntimeException(sprintf(
                'function pll_languages_list not found'
            ));
        }
        if (!empty($language) && !in_array($language, pll_languages_list())) {
            throw new Exception\UnexpectedValueException(sprintf(
                'Language not found'
            ));
        }

        $this->language = $language;

        return parent::__construct($feedUri);
    }

    /**
     * @inheritDoc
     */
    protected function entryIsNew(EntryInterface $entry)
    {
        $query = new WP_Query([
            'post_type'   => $this->postType,
            'post_status' => 'any',
            'lang'        => $this->language,
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
     * @inheritDoc
     */
    protected function createPostFromEntry(EntryInterface $entry)
    {
        $postId = parent::createPostFromEntry($entry);

        if (is_int($postId) && $this->language && function_exists('pll_set_post_language')) {
                pll_set_post_language($postId, $this->language);
        }

        return $postId;
    }

    /**
     * @inheritDoc
     */
    protected function createTermsFromEntry($postId, EntryInterface $entry)
    {
        $ids = parent::createTermsFromEntry($postId, $entry);

        if (!empty($ids) && $this->language && function_exists('pll_set_post_language')) {
            foreach ($ids as $id) {
                pll_set_term_language($is, $this->language);
            }
        }
        return $ids;
    }
}
