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
    $finalResult = [];
    if (!empty($_REQUEST['mode']) && !empty($_REQUEST['entityId']) && !empty($_REQUEST['entityTable'])) {
      $mode = CRM_Utils_Type::escape($_REQUEST['mode'], 'Integer');
      $entityId = CRM_Utils_Type::escape($_REQUEST['entityId'], 'Integer');
      $entityTable = CRM_Utils_Type::escape($_REQUEST['entityTable'], 'String');
      $priceSet = CRM_Utils_Type::escape($_REQUEST['priceSet'], 'String');

      // CRM-21764 fix
      // Retrieving existing priceset if price set id is not passed
      if ($priceSet == "") {
        $priceSetEntity = new CRM_Price_DAO_PriceSetEntity();
        $priceSetEntity->entity_id = $entityId;
        $priceSetEntity->entity_table = $entityTable;
        $priceSetEntity->find(TRUE);
        $priceSet = $priceSetEntity->price_set_id;
      }
      $linkedEntityTable = $_REQUEST['linkedEntityTable'];
      $finalResult = CRM_Core_BAO_RecurringEntity::updateModeAndPriceSet($entityId, $entityTable, $mode, $linkedEntityTable, $priceSet);
    }
    CRM_Utils_JSON::output($finalResult);
  }

}
