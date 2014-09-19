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
    if(CRM_Utils_Array::value('mode', $_REQUEST) && CRM_Utils_Array::value('entityId', $_REQUEST) && CRM_Utils_Array::value('entityTable', $_REQUEST)){
      
      $finalResult = array();
      $mode = CRM_Utils_Type::escape($_REQUEST['mode'], 'Integer');
      $entityId = CRM_Utils_Type::escape($_REQUEST['entityId'], 'Integer');
      $entityTable = CRM_Utils_Type::escape($_REQUEST['entityTable'], 'String');

      if(CRM_Utils_Array::value('linkedEntityTable', $_REQUEST)){
        $result = array();
        $result = CRM_Event_BAO_Event::updateModeRecurringEntityForEvent($entityId, $_REQUEST['linkedEntityTable']);
      }

      $dao = new CRM_Core_DAO_RecurringEntity();
      if(!empty($result)){
        $dao->entity_id = $result['entityId'];
        $dao->entityTable = $result['entityTable'];
      }else{
        $dao->entity_id = $entityId;
        $dao->entityTable = $entityTable;
      }
      
      if($dao->find(TRUE)){
        $dao->mode = $mode;
        $dao->save();
        $finalResult['status'] = 'Done';
      }else{
        $finalResult['status'] = 'Error';
      }
    }
    echo json_encode($finalResult);
    CRM_Utils_System::civiExit();
  }
  
  public static function generatePreview(){
    $params = $formValues = $genericResult = array();
    $formValues = $_REQUEST;
    if(!empty($formValues)){
      $recursion = new CRM_Core_BAO_RecurringEntity();
      $recursion->dateColumns  = array('start_date');
      $recursion->scheduleFormValues = $formValues;
      if (!empty($formValues['exclude_date_list'])) {
        $recursion->excludeDates = $formValues['exclude_date_list'];
        $recursion->excludeDateRangeColumns = array('start_date', 'end_date');
      }

      $parentEventId = CRM_Core_BAO_RecurringEntity::getParentFor($formValues['event_id'], 'civicrm_event');
      if(!$parentEventId){
        $parentEventId = $formValues['event_id'];
      }

      $endDate = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $parentEventId, 'end_date');
      if ($endDate) {
        $startDate = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $parentEventId, 'start_date');
        $interval  = $recursion->getInterval($startDate, $endDate);
        $recursion->intervalDateColumns = array('end_date' => $interval);
      }

      $result = $recursion->generateRecursiveDates(); 

      foreach ($result as $key => $value) {
        $result[$key]['start_date'] = date('M d, Y h:i:s A \o\n l', strtotime($value['start_date']));
        if($value['end_date']){
          $result[$key]['end_date'] = date('M d, Y h:i:s A \o\n l', strtotime($value['end_date']));
        }
      }

      //Show the list of participants registered for the events if any
      $getConnectedEntities = CRM_Core_BAO_RecurringEntity::getEntitiesForParent($parentEventId, 'civicrm_event', TRUE);
      if($getConnectedEntities){
        $participantDetails = CRM_Event_Form_ManageEvent_Repeat::getParticipantCountforEvent($getConnectedEntities);
        if(!empty($participantDetails['countByName'])){
          $result['participantData'] = $participantDetails['countByName'];
        }
      }
    }
    echo json_encode($result);
    CRM_Utils_System::civiExit();
  }
}
