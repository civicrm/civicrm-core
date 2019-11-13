<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */


namespace Civi\Api4\Event;

use Civi\Api4\Query\Api4SelectQuery;
use Symfony\Component\EventDispatcher\Event;

class PostSelectQueryEvent extends Event {

  /**
   * @var array
   */
  protected $results;

  /**
   * @var \Civi\Api4\Query\Api4SelectQuery
   */
  protected $query;

  /**
   * PostSelectQueryEvent constructor.
   * @param array $results
   * @param \Civi\Api4\Query\Api4SelectQuery $query
   */
  public function __construct(array $results, Api4SelectQuery $query) {
    $this->results = $results;
    $this->query = $query;
  }

  /**
   * @return array
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * @param array $results
   * @return $this
   */
  public function setResults($results) {
    $this->results = $results;

    return $this;
  }

  /**
   * @return \Civi\Api4\Query\Api4SelectQuery
   */
  public function getQuery() {
    return $this->query;
  }

  /**
   * @param \Civi\Api4\Query\Api4SelectQuery $query
   * @return $this
   */
  public function setQuery($query) {
    $this->query = $query;

    return $this;
  }

}
