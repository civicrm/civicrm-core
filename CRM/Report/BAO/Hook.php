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
 */

/**
 * Report hooks that allow extending a particular report.
 * Example: Adding new tables to log reports
 */
class CRM_Report_BAO_Hook {

  /**
   * @var \CRM_Report_BAO_HookInterface[]
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
   * @param string $table
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
