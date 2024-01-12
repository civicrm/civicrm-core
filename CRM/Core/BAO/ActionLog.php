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
 * This class contains functions for managing Action Logs
 */
class CRM_Core_BAO_ActionLog extends CRM_Core_DAO_ActionLog {

  /**
   * Create or update an action log entry.
   *
   * @param array $params
   *
   * @return CRM_Core_DAO_ActionLog
   */
  public static function create($params) {
    if (empty($params['id'])) {
      $params['action_date_time'] ??= date('YmdHis');
    }

    return self::writeRecord($params);
  }

}
