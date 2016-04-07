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
     * @throws Exception\UnexpectedValueException When the uri of the feed is not set (see setFeedUri)
     * @throws Zend\Feed\Reader\Exception\RuntimeException For Zend\Feed\Reader Exception during import
     */
    public function import();

    /**
     * Configure the entry to create.
     *
     * @param (string) $postType the type of the post to create (by default : "post")
     * @param (array) $params params for the new entry (all optional) :
     *   * (callable) 'afterInsertPost' - a callable called after the insertion of a new content.
     *      It provides two arguments : (integer) $postId and Zend\Feed\Reader\Entry\EntryInterface $entry
     *   * (callable) 'afterInsertTerm' - a callable called after the insertion of a new taxonomy term.
     *      It provides one argument : (integer) $termId
     *   * (string) 'taxonomy' - The name of the taxonomy to use if the entry have "category" elements.
     *   * (integer) 'authorId' - The id of the author, default to 1.
     *
     * @throws Exception\UnexpectedValueException When the post type does not exist
     * @throws Exception\UnexpectedValueException When the taxonomy does not exist
     * @throws Exception\InvalidArgumentException When $params['afterInsert'] is set and is not a callable
     *
     * @return self
     */
    public function configureEntry($postType = 'post', $params = []);

    /**
     * Configure the feed uri.
     *
     * @param (string) $uri The URI to the feed
     *
     * @return self
     */
    public function setFeedUri($uri);
}
