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

/**
 * This class contains functions for managing Action Logs
 */
class CRM_Core_BAO_ActionLog extends CRM_Core_DAO_ActionLog {

  /**
   * Create or update an action log entry.
   *
   * @param array $params
   *
   * @return array
   */
  public static function create($params) {
    $actionLog = new CRM_Core_DAO_ActionLog();

    $params['action_date_time'] = CRM_Utils_Array::value('action_date_time', $params, date('YmdHis'));

    $actionLog->copyValues($params);

    $edit = ($actionLog->id) ? TRUE : FALSE;
    if ($edit) {
      CRM_Utils_Hook::pre('edit', 'ActionLog', $actionLog->id, $actionLog);
    }
    else {
      CRM_Utils_Hook::pre('create', 'ActionLog', NULL, $actionLog);
    }

    $actionLog->save();

    if ($edit) {
      CRM_Utils_Hook::post('edit', 'ActionLog', $actionLog->id, $actionLog);
    }
    else {
      CRM_Utils_Hook::post('create', 'ActionLog', NULL, $actionLog);
    }

    return $actionLog;
  }

}
