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
    $mode = CRM_Utils_Request::retrieve('mode', 'Integer');
    $entityId = CRM_Utils_Request::retrieve('entityId', 'Positive');
    $entityTable = CRM_Utils_Request::retrieve('entityTable', 'String');
    $priceSet = CRM_Utils_Request::retrieve('priceSet', 'String');
    $linkedEntityTable = CRM_Utils_Request::retrieve('linkedEntityTable', 'String');

    if ($mode && $entityId && $entityTable) {
      if ($entityTable === 'civicrm_event') {
        if (!CRM_Core_Component::isEnabled('CiviEvent') || !CRM_Event_BAO_Event::checkPermission($entityId, CRM_Core_Permission::EDIT)) {
          CRM_Utils_System::permissionDenied();
        }
      }
      elseif ($entityTable === 'civicrm_activity') {
        if (!CRM_Activity_BAO_Activity::checkPermission($entityId, CRM_Core_Permission::EDIT)) {
          CRM_Utils_System::permissionDenied();
        }
      }
      elseif (!CRM_Core_Permission::check('administer CiviCRM')) {
        CRM_Utils_System::permissionDenied();
      }

      // CRM-21764 fix
      // Retrieving existing priceset if price set id is not passed
      if ($priceSet === '' || $priceSet === NULL) {
        $priceSetEntity = new CRM_Price_DAO_PriceSetEntity();
        $priceSetEntity->entity_id = $entityId;
        $priceSetEntity->entity_table = $entityTable;
        if ($priceSetEntity->find(TRUE)) {
          $priceSet = $priceSetEntity->price_set_id;
        }
      }
      $finalResult = CRM_Core_BAO_RecurringEntity::updateModeAndPriceSet($entityId, $entityTable, $mode, $linkedEntityTable, $priceSet);
    }
    CRM_Utils_JSON::output($finalResult);
  }

}
