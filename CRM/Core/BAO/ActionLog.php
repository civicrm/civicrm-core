<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright (C) 2011 Marty Wright                                    |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class contains functions for managing Action Logs
 */
class CRM_Core_BAO_ActionLog extends CRM_Core_DAO_ActionLog {

  /**
   * Create or update an action log entry
   *
   * @param $params
   *
   * @return actionLog array
   * @access public
   */
  static function create($params) {
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

