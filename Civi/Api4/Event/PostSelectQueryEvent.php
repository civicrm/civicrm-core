<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
