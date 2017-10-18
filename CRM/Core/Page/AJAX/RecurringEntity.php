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

        //CRM-20787 Fix
        //I am not sure about other fields, if mode = 3 apply for an event then other fields
        //should be save for all other series events or not so applying for price set only for now here.
        if (CRM_Core_BAO_RecurringEntity::MODE_ALL_ENTITY_IN_SERIES === $mode) {

          //Step-1: Get all events of series
          $seriesEventRecords = CRM_Core_BAO_RecurringEntity::getEntitiesFor($entityId, $entityTable);
          foreach ($seriesEventRecords as $event) {
            //Step-3: Save price set in other series events
            if (CRM_Price_BAO_PriceSet::removeFrom($event['table'], $event['id'])) {//Remove existing priceset
              CRM_Core_BAO_Discount::del($event['id'], $event['table']);
              CRM_Price_BAO_PriceSet::addTo($event['table'], $event['id'], $priceSet); //Add new price set
            }
          }
        }
        //CRM-20787 - Fix end
        $finalResult['status'] = 'Done';
      }
      else {
        $finalResult['status'] = 'Error';
      }
    }
    CRM_Utils_JSON::output($finalResult);
  }

}
