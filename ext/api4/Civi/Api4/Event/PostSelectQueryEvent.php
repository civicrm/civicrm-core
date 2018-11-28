<?php

namespace Civi\Api4\Event;

use Civi\Api4\Query\Api4SelectQuery;
use Symfony\Component\EventDispatcher\Event;

class PostSelectQueryEvent extends Event {

  /**
   * @var array
   */
  protected $results;

  /**
   * @var Api4SelectQuery
   */
  protected $query;

  /**
   * PostSelectQueryEvent constructor.
   * @param array $results
   * @param Api4SelectQuery $query
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
   * @return Api4SelectQuery
   */
  public function getQuery() {
    return $this->query;
  }

  /**
   * @param Api4SelectQuery $query
   * @return $this
   */
  public function setQuery($query) {
    $this->query = $query;

    return $this;
  }

}
