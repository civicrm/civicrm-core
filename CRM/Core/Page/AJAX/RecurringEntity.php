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
    if (CRM_Utils_Array::value('mode', $_REQUEST) && CRM_Utils_Array::value('entityId', $_REQUEST) && CRM_Utils_Array::value('entityTable', $_REQUEST)) {

      $finalResult = array();
      $mode = CRM_Utils_Type::escape($_REQUEST['mode'], 'Integer');
      $entityId = CRM_Utils_Type::escape($_REQUEST['entityId'], 'Integer');
      $entityTable = CRM_Utils_Type::escape($_REQUEST['entityTable'], 'String');

      if (CRM_Utils_Array::value('linkedEntityTable', $_REQUEST)) {
        $result = array();
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
    echo json_encode($finalResult);
    CRM_Utils_System::civiExit();
  }

  public static function generatePreview() {
    $params = $formValues = $genericResult = array();
    $formValues = $_REQUEST;
    if (!empty($formValues) &&
      CRM_Utils_Array::value('entity_table', $formValues)
    ) {
      $startDateColumnName = CRM_Core_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']]['dateColumns'][0];
      $endDateColumnName = CRM_Core_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']]['intervalDateColumns'][0];

      $recursion = new CRM_Core_BAO_RecurringEntity();
      if (CRM_Utils_Array::value('dateColumns', CRM_Core_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']])) {
        $recursion->dateColumns = CRM_Core_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']]['dateColumns'];
      }
      $recursion->scheduleFormValues = $formValues;
      if (!empty($formValues['exclude_date_list'])) {
        $recursion->excludeDates = $formValues['exclude_date_list'];
      }
      if (CRM_Utils_Array::value('excludeDateRangeColumns', CRM_Core_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']])) {
        $recursion->excludeDateRangeColumns = CRM_Core_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']]['excludeDateRangeColumns'];
      }

      if (CRM_Utils_Array::value('entity_id', $formValues)) {
        $parentEventId = CRM_Core_BAO_RecurringEntity::getParentFor($formValues['entity_id'], $formValues['entity_table']);
      }

      //Check if there is any enddate column defined to find out the interval between the two range
      if (CRM_Utils_Array::value('intervalDateColumns', CRM_Core_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']])) {
        $daoName = CRM_Core_BAO_RecurringEntity::$_tableDAOMapper[$formValues['entity_table']];
        if ($parentEventId) {
          $startDate = CRM_Core_DAO::getFieldValue($daoName, $parentEventId, $startDateColumnName);
          $endDate = CRM_Core_DAO::getFieldValue($daoName, $parentEventId, $endDateColumnName);
        }
        if ($endDate) {
          $interval = $recursion->getInterval($startDate, $endDate);
          $recursion->intervalDateColumns = array($endDateColumnName => $interval);
        }
      }

      $result = $recursion->generateRecursiveDates();

      foreach ($result as $key => $value) {
        if ($startDateColumnName) {
          $result[$key]['start_date'] = date('M d, Y h:i:s A \o\n l', strtotime($value[$startDateColumnName]));
        }
        if ($value[$endDateColumnName]) {
          if ($endDateColumnName) {
            $result[$key]['end_date'] = date('M d, Y h:i:s A \o\n l', strtotime($value[$endDateColumnName]));
          }
        }
      }

      //Show the list of participants registered for the events if any
      if ($formValues['entity_table'] == "civicrm_event" && !empty($parentEventId)) {
        $getConnectedEntities = CRM_Core_BAO_RecurringEntity::getEntitiesForParent($parentEventId, 'civicrm_event', TRUE);
        if ($getConnectedEntities) {
          $participantDetails = CRM_Event_Form_ManageEvent_Repeat::getParticipantCountforEvent($getConnectedEntities);
          if (!empty($participantDetails['countByName'])) {
            $result['participantData'] = $participantDetails['countByName'];
          }
        }
      }
    }
    echo json_encode($result);
    CRM_Utils_System::civiExit();
  }

}
