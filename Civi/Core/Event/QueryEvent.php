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

namespace Civi\Core\Event;

/**
 * Class QueryEvent
 * @package Civi\Core\Event
 *
 * The QueryEvent fires whenever a SQL query is executed.
 */
class QueryEvent extends \Symfony\Component\EventDispatcher\Event {

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
