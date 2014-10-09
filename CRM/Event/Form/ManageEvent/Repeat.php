<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Repeat
 *
 * @author Priyanka
 */
class CRM_Event_Form_ManageEvent_Repeat extends CRM_Event_Form_ManageEvent {
  
  /**
   * Parent Event Start Date
   */
  protected $_parentEventStartDate = NULL;
  
  /**
   * Parent Event End Date
   */
  protected $_parentEventEndDate = NULL;
  
  
  function preProcess() {
    parent::preProcess();
    CRM_Core_Form_RecurringEntity::preProcess('event');
    $this->assign('currentEventId', $this->_id);
    
    $checkParentExistsForThisId = CRM_Core_BAO_RecurringEntity::getParentFor($this->_id, 'civicrm_event');    
    //If this ID has parent, send parent id
    if ($checkParentExistsForThisId) {
      /**
     * Get connected event information list
     */
      //Get all connected event ids
      //$allEventIds = CRM_Core_Form_RecurringEntity::getAllConnectedEvents($checkParentExistsForThisId);
      $allEventIdsArray = CRM_Core_BAO_RecurringEntity::getEntitiesForParent($checkParentExistsForThisId, 'civicrm_event');
      $allEventIds = array();
      if (!empty($allEventIdsArray)) {
        foreach($allEventIdsArray as $key => $val) {
          $allEventIds[] = $val['id'];
        }
        if (!empty($allEventIds)) {
          $params = array();
          $query = "
            SELECT *
            FROM civicrm_event
            WHERE id IN (".implode(",", $allEventIds).")
            ORDER BY start_date asc
             ";

          $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Event_DAO_Event');
          $permissions = CRM_Event_BAO_Event::checkPermission();
          while($dao->fetch()) {
            if (in_array($dao->id, $permissions[CRM_Core_Permission::VIEW])) {
              $manageEvent[$dao->id] = array();
              CRM_Core_DAO::storeValues($dao, $manageEvent[$dao->id]);
            }
          }
        }  
        $this->assign('rows', $manageEvent);
      }
    }

    //FIX ME : For summary
    //Assign this to hide summary
//    if (property_exists($this->_scheduleReminderDetails, 'id')) {
//      $this->assign('scheduleReminderId', $this->_scheduleReminderDetails->id);
//    }

    $parentEventParams = array('id' => $this->_id);
    $parentEventValues = array();
    $parentEventReturnProperties = array('start_date', 'end_date');
    $parentEventAttributes = CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Event', $parentEventParams, $parentEventValues, $parentEventReturnProperties);
    $this->_parentEventStartDate = $parentEventAttributes->start_date;
    $this->_parentEventEndDate = $parentEventAttributes->end_date;
  }
  
  /**
   * This function sets the default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = array();
    
    //Always pass current event's start date by default
    $currentEventStartDate = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_id, 'start_date', 'id');
    list($defaults['repetition_start_date'], $defaults['repetition_start_date_time']) = CRM_Utils_Date::setDateDefaults($currentEventStartDate, 'activityDateTime');
    $recurringEntityDefaults = array();
    $recurringEntityDefaults = CRM_Core_Form_RecurringEntity::setDefaultValues();
    $defaults = array_merge($defaults, $recurringEntityDefaults);
    return $defaults;
  }
  
  public function buildQuickForm() {
    CRM_Core_Form_RecurringEntity::buildQuickForm($this);
  }
   
  public function postProcess() {
    if ($this->_id) {
      $params = $this->controller->exportValues($this->_name); 
      $params['parent_entity_start_date'] = $this->_parentEventStartDate;
      $params['parent_entity_end_date'] = $this->_parentEventEndDate;
      $params['start_date_column_name'] = 'start_date';
      $params['end_date_column_name'] = 'end_date';
      $params['entity_table'] = 'civicrm_event';
      //Unset event id
      unset($params['id']);

      $url = 'civicrm/event/manage/repeat';
      $urlParams = "action=update&reset=1&id={$this->_id}";
      
      $linkedEntities = array(
        array(
          'table'         => 'civicrm_price_set_entity',
          'findCriteria'  => array(
            'entity_id'    => $this->_id, 
            'entity_table' => 'civicrm_event'
          ),
          'linkedColumns' => array('entity_id'),
          'isRecurringEntityRecord' => FALSE,
        ),
        array(
          'table'         => 'civicrm_uf_join',
          'findCriteria'  => array(
            'entity_id'    => $this->_id, 
            'entity_table' => 'civicrm_event'
          ),
          'linkedColumns' => array('entity_id'),
          'isRecurringEntityRecord' => FALSE,
        ),
        array(
          'table'         => 'civicrm_tell_friend',
          'findCriteria'  => array(
            'entity_id'    => $this->_id, 
            'entity_table' => 'civicrm_event'
          ),
          'linkedColumns' => array('entity_id'),
          'isRecurringEntityRecord' => TRUE,
        ),
        array(
          'table'         => 'civicrm_pcp_block',
          'findCriteria'  => array(
            'entity_id'    => $this->_id, 
            'entity_table' => 'civicrm_event'
          ),
          'linkedColumns' => array('entity_id'),
          'isRecurringEntityRecord' => TRUE,
        ),
      );
      CRM_Core_Form_RecurringEntity::postProcess($params, 'event', $linkedEntities);
      CRM_Utils_System::redirect(CRM_Utils_System::url($url, $urlParams));
    }
    else {
        CRM_Core_Error::fatal("Could not find Event ID");
    }  
  }
  
   /**
   * This function gets the number of participant count for the list of related event ids
   * 
   * @param array $listOfRelatedEntities list of related event ids
   * 
   * @access public
   * @static
   * 
   * @return array
   */
  static public function getParticipantCountforEvent($listOfRelatedEntities = array()) {
    if (!empty($listOfRelatedEntities)) {
      $implodeRelatedEntities = implode(',', array_map(function($entity) {
        return $entity['id'];
      }, $listOfRelatedEntities));
      if ($implodeRelatedEntities) {
        $query = "SELECT p.event_id as event_id, 
          concat_ws(' ', e.title, concat_ws(' - ', DATE_FORMAT(e.start_date, '%b %d %Y %h:%i %p'), DATE_FORMAT(e.end_date, '%b %d %Y %h:%i %p'))) as event_data, 
          count(p.id) as participant_count
          FROM civicrm_participant p, civicrm_event e 
          WHERE p.event_id = e.id AND p.event_id IN ({$implodeRelatedEntities})
          GROUP BY p.event_id";
        $dao = CRM_Core_DAO::executeQuery($query);
        $participantDetails = array();
        while($dao->fetch()) {
          $participantDetails['countByID'][$dao->event_id] = $dao->participant_count;
          $participantDetails['countByName'][$dao->event_id][$dao->event_data] = $dao->participant_count;
        }
      }
    }
    return $participantDetails;
  }
  
  /**
  * Update mode column in civicrm_recurring_entity table for event related tabs
  * 
  * @params int $entityId event id
  * @params string $linkedEntityTable Linked entity table name for this event
  * @return array
  */
  public static function updateModeRecurringEntityForEvent($entityId, $linkedEntityTable) {
    $result = array();
    if ( $entityId && $linkedEntityTable ) {
      switch ($linkedEntityTable) {
        case 'civicrm_tell_friend':
          $dao = 'CRM_Friend_DAO_Friend';
          $entityTable = 'civicrm_tell_friend';
          break;

        case 'civicrm_pcp_block':
          $dao = 'CRM_PCP_DAO_PCPBlock';
          $entityTable = 'civicrm_pcp_block';
          break;

        case 'civicrm_price_set_entity':
          $dao = 'CRM_Price_DAO_PriceSetEntity';
          $entityTable = 'civicrm_price_set_entity';
          break;

        case 'civicrm_uf_join':
          $dao = 'CRM_Core_DAO_UFJoin';
          $entityTable = 'civicrm_uf_join';
          break;
        }
        $params = array(
                        'entity_id' => $entityId,
                        'entity_table' => 'civicrm_event'
                      );
        $defaults = array();
        CRM_Core_DAO::commonRetrieve($dao, $params, $defaults);
        if (CRM_Utils_Array::value('id', $defaults)) {
          $result['entityId'] = $defaults['id'];
          $result['entityTable'] = $entityTable;
        }
    } 
    return $result;
  } 
  
  /**
   * This function checks if there was any registraion for related event ids,
   * and returns array of ids with no regsitrations
   * @param type $eventID Event ID
   * @return type
   */
  public static function checkRegistrationForEvents($eventID) {
    $eventIdsWithNoRegistration = array();
    if ($eventID) {
      $getRelatedEntities = CRM_Core_BAO_RecurringEntity::getEntitiesFor($eventID, 'civicrm_event', TRUE);
      $participantDetails = CRM_Event_Form_ManageEvent_Repeat::getParticipantCountforEvent($getRelatedEntities);
      //Check if participants exists for events
      foreach ($getRelatedEntities as $key => $value) {
        if (!CRM_Utils_Array::value($value['id'], $participantDetails['countByID']) && $value['id'] != $eventID) {
          //CRM_Event_BAO_Event::del($value['id']);
          $eventIdsWithNoRegistration[] = $value['id'];
        }
      }
    }
    CRM_Core_BAO_RecurringEntity::$_entitiesToBeDeleted = $eventIdsWithNoRegistration;
    return CRM_Core_BAO_RecurringEntity::$_entitiesToBeDeleted;
  }
}
