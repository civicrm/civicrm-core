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
    if (CRM_Utils_Array::value('mode', $_REQUEST) && CRM_Utils_Array::value('entityId', $_REQUEST) && CRM_Utils_Array::value('entityTable', $_REQUEST) && CRM_Utils_Array::value('priceSet', $_REQUEST)) {

      $mode = CRM_Utils_Type::escape($_REQUEST['mode'], 'Integer');
      $entityId = CRM_Utils_Type::escape($_REQUEST['entityId'], 'Integer');
      $entityTable = CRM_Utils_Type::escape($_REQUEST['entityTable'], 'String');
      $priceSet = CRM_Utils_Type::escape($_REQUEST['priceSet'], 'String');
      $linkedEntityTable = $_REQUEST['linkedEntityTable'];
      $finalResult = CRM_Core_BAO_RecurringEntity::updateModeAndPriceSet($entityId, $entityTable, $mode, $linkedEntityTable, $priceSet);
    }
    CRM_Utils_JSON::output($finalResult);
  }

}
