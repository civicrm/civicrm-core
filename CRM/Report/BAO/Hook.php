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

/**
 * Report hooks that allow extending a particular report.
 * Example: Adding new tables to log reports
 */
class CRM_Report_BAO_Hook {

  /**
   * @var array of CRM_Report_BAO_HookInterface objects
   */
  protected $_queryObjects = NULL;

  /**
   * Singleton function used to manage this object.
   *
   * @return object
   */
  public static function singleton() {
    static $singleton = NULL;
    if (!$singleton) {
      $singleton = new CRM_Report_BAO_Hook();
    }
    return $singleton;
  }

  /**
   * Get or build the list of search objects (via hook)
   *
   * @return array
   *   Array of CRM_Report_BAO_Hook_Interface objects
   */
  public function getSearchQueryObjects() {
    if ($this->_queryObjects === NULL) {
      $this->_queryObjects = [];
      CRM_Utils_Hook::queryObjects($this->_queryObjects, 'Report');
    }
    return $this->_queryObjects;
  }

  /**
   * @param $reportObj
   * @param $logTables
   */
  public function alterLogTables(&$reportObj, &$logTables) {
    foreach (self::getSearchQueryObjects() as $obj) {
      $obj->alterLogTables($reportObj, $logTables);
    }
  }

  /**
   * @param $reportObj
   * @param $table
   *
   * @return array
   */
  public function logDiffClause(&$reportObj, $table) {
    $contactIdClause = $join = '';
    foreach (self::getSearchQueryObjects() as $obj) {
      list($cidClause, $joinClause) = $obj->logDiffClause($reportObj, $table);
      if ($joinClause) {
        $join .= $joinClause;
      }
      if ($cidClause) {
        $contactIdClause .= $cidClause;
      }
    }
    return [$contactIdClause, $join];
  }

}
