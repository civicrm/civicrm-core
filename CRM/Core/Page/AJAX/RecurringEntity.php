<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EntityApplyChangesTo
 *
 * @author Priyanka
 */
class CRM_Core_Page_AJAX_RecurringEntity {

  public static function updateMode() {
    $finalResult = array();
    if (CRM_Utils_Array::value('mode', $_REQUEST) && CRM_Utils_Array::value('entityId', $_REQUEST) && CRM_Utils_Array::value('entityTable', $_REQUEST)) {

      $mode = CRM_Utils_Type::escape($_REQUEST['mode'], 'Integer');
      $entityId = CRM_Utils_Type::escape($_REQUEST['entityId'], 'Integer');
      $entityTable = CRM_Utils_Type::escape($_REQUEST['entityTable'], 'String');

      if (!empty($_REQUEST['linkedEntityTable'])) {
        $result = CRM_Core_BAO_RecurringEntity::updateModeLinkedEntity($entityId, $_REQUEST['linkedEntityTable'], $entityTable);
      }

      $dao = new CRM_Core_DAO_RecurringEntity();
      if (!empty($result)) {
        $dao->entity_id = $result['entityId'];
        $dao->entity_table = $result['entityTable'];
      }
      else {
        $dao->entity_id = $entityId;
        $dao->entity_table = $entityTable;
      }

      if ($dao->find(TRUE)) {
        $dao->mode = $mode;
        $dao->save();
        $finalResult['status'] = 'Done';
      }
      else {
        $finalResult['status'] = 'Error';
      }
    }
    CRM_Utils_JSON::output($finalResult);
  }

}
