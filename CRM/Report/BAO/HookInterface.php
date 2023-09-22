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
 * Interface class for Report hook query objects
 */
class CRM_Report_BAO_HookInterface {

  /**
   * @param $reportObj
   * @param $logTables
   *
   * @return null
   */
  public function alterLogTables(&$reportObj, &$logTables) {
    return NULL;
  }

  /**
   * @param $reportObj
   * @param string $table
   *
   * @return array
   */
  public function logDiffClause(&$reportObj, $table) {
    return [];
  }

}
