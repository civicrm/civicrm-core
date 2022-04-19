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

namespace Civi\Core\Event;

/**
 * Class QueryEvent
 * @package Civi\Core\Event
 *
 * The QueryEvent fires whenever a SQL query is executed.
 */
class QueryEvent extends GenericHookEvent {

  /**
   * @var string
   */
  public $query;

  private $verb = NULL;

  /**
   * QueryEvent constructor.
   * @param string $query
   */
  public function __construct($query) {
    $this->query = $query;
  }

  /**
   * @return string|FALSE
   *   Ex: 'SELECT', 'INSERT', 'CREATE', 'ALTER'
   *   A FALSE value indicates that a singular verb could not be identified.
   */
  public function getVerb() {
    if ($this->verb === NULL) {
      if (preg_match(';(/\*.*/\*\s*)?([a-zA-Z]+) ;', $this->query, $m)) {
        $this->verb = strtolower($m[2]);
      }
      else {
        $this->verb = FALSE;
      }
    }
    return $this->verb;
  }

}
