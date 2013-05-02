<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * this file contains functions to manage and manipulate task status
 */
class CRM_Project_BAO_TaskStatus {

  static function &getTaskStatusInitial(&$controller,
    $ret, $reid,
    $tet, $teid,
    $taskID,
    $prefix = 'taskStatus',
    $statusDetail = TRUE
  ) {

    $taskStatusID = $controller->get("{$prefix}ID");
    $taskStatus = $controller->get($prefix);

    if (!$taskStatusID) {
      // cache the status
      $status = CRM_Core_OptionGroup::values('task_status', TRUE);

      // get the task status object, if not there create one
      $dao = new CRM_Project_DAO_TaskStatus();
      $dao->responsible_entity_table = $ret;
      $dao->responsible_entity_id = $reid;
      $dao->target_entity_table = $tet;
      $dao->target_entity_id = $teid;
      $dao->task_id = $taskID;

      if (!$dao->find(TRUE)) {
        $dao->create_date = date('YmdHis');
        $dao->status_id = $status['Not Started'];
        $dao->save();
      }

      if ($statusDetail && $dao->status_detail) {
        $data = &$controller->container();
        $data['valid'] = unserialize($dao->status_detail);
      }
      $controller->set("{$prefix}ID", $dao->id);

      $taskStatus = array_search($dao->status_id, $status);
      $controller->set($prefix, $taskStatus);
    }

    $controller->assign($prefix, $taskStatus);

    return array($taskStatusID, $taskStatus);
  }

  static function updateTaskStatus(&$form,
    $prefix = 'taskStatus',
    $statusDetail = TRUE
  ) {

    // update the task record
    $dao = new CRM_Project_DAO_TaskStatus();
    $dao->id = $form->get("{$prefix}ID");
    if (!$dao->id || !$dao->find(TRUE)) {
      CRM_Core_Error::fatal("The task status table is inconsistent");
    }

    $status = CRM_Core_OptionGroup::values('task_status', TRUE);
    if ($form->controller->isApplicationComplete()) {
      $dao->status_id = $status['Completed'];
      $form->set($prefix, 'Completed');
    }
    else {
      $dao->status_id = $status['In Progress'];
      $form->set($prefix, 'In Progress');
    }

    $dao->create_date = CRM_Utils_Date::isoToMysql($dao->create_date);
    $dao->modified_date = date('YmdHis');

    if ($statusDetail) {
      // now save all the valid values to fool QFC
      $data = &$form->controller->container();
      // CRM_Core_Error::debug( 'd', $data );
      $dao->status_detail = serialize($data['valid']);
    }

    $dao->save();
  }

  static function updateTaskStatusWithValue(&$form,
    $value = 'In Progress',
    $prefix = 'taskStatus'
  ) {

    // update the task record
    $dao = new CRM_Project_DAO_TaskStatus();
    $dao->id = $form->get("{$prefix}ID");
    if (!$dao->id || !$dao->find(TRUE)) {
      CRM_Core_Error::fatal("The task status table is inconsistent");
    }

    $status = CRM_Core_OptionGroup::values('task_status', TRUE);
    $dao->status_id = $status[$value];
    $form->set($prefix, $value);

    $dao->create_date = CRM_Utils_Date::isoToMysql($dao->create_date);
    $dao->modified_date = date('YmdHis');

    $dao->save();
  }

  /**
   * Function to set the task status of various tasks
   *
   * @param array  $params        associated array
   *
   * @static
   *
   * @return returns task status object
   */
  static function create(&$params) {
    if (!$params['target_entity_id'] || !$params['responsible_entity_id']
      || !$params['task_id'] || !$params['status_id']
    ) {
      return NULL;
    }

    if (!$params['target_entity_table']) {
      $params['target_entity_table'] = 'civicrm_contact';
    }

    if (!$params['responsible_entity_table']) {
      $params['responsible_entity_table'] = 'civicrm_contact';
    }

    $dao = new CRM_Project_DAO_TaskStatus();
    $dao->target_entity_id = $params['target_entity_id'];
    $dao->responsible_entity_id = $params['responsible_entity_id'];
    $dao->target_entity_table = $params['target_entity_table'];
    $dao->responsible_entity_table = $params['responsible_entity_table'];
    $dao->task_id = $params['task_id'];

    if ($dao->find(TRUE)) {
      $dao->create_date = CRM_Utils_Date::isoToMysql($dao->create_date);
    }
    else {
      $dao->create_date = date('YmdHis');
    }
    $dao->modified_date = date('YmdHis');
    $dao->status_id = $params['status_id'];

    return $dao->save();
  }
}

