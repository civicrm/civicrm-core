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
   * Schedule Reminder Id
   */
  protected $_scheduleReminderId = NULL;

  /**
   * Schedule Reminder data
   */
  protected $_scheduleReminderDetails = array();

  /**
   *  Parent Event ID
   */
  protected $_parentEventId = NULL;

  /**
   * Parent Event Start Date
   */
  protected $_parentEventStartDate = NULL;

  /**
   * Parent Event End Date
   */
  protected $_parentEventEndDate = NULL;

  /**
   * Exclude date information
   */
  public $_excludeDateInfo = array();

  protected $_pager = NULL;



  function preProcess() {
    parent::preProcess();
    $this->assign('currentEventId', $this->_id);

    $checkParentExistsForThisId = CRM_Core_BAO_RecurringEntity::getParentFor($this->_id, 'civicrm_event');
    $checkParentExistsForThisId;
    //If this ID has parent, send parent id
    if ($checkParentExistsForThisId) {
      $this->_scheduleReminderDetails = self::getReminderDetailsByEventId($checkParentExistsForThisId, 'event');
      $this->_parentEventId = $checkParentExistsForThisId;

      /**
     * Get connected event information list
     */
      //Get all connected event ids
      //$allEventIds = CRM_Core_Form_RecurringEntity::getAllConnectedEvents($checkParentExistsForThisId);
      $allEventIdsArray = CRM_Core_BAo_RecurringEntity::getEntitiesForParent($checkParentExistsForThisId, 'civicrm_event');
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
    else {
      //ELse send this id as parent
      $this->_scheduleReminderDetails = self::getReminderDetailsByEventId($this->_id, 'event');
      $this->_parentEventId = $this->_id;
    }

    //Assign this to hide summary
    if (property_exists($this->_scheduleReminderDetails, 'id')) {
      $this->assign('scheduleReminderId', $this->_scheduleReminderDetails->id);
    }

    $parentEventParams = array('id' => $this->_id);
    $parentEventValues = array();
    $parentEventReturnProperties = array('start_date', 'end_date');
    $parentEventAttributes = CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Event', $parentEventParams, $parentEventValues, $parentEventReturnProperties);
    $this->_parentEventStartDate = $parentEventAttributes->start_date;
    $this->_parentEventEndDate = $parentEventAttributes->end_date;

    //Get option exclude date information
    //$groupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'event_repeat_exclude_dates_'.$this->_parentEventId, 'id', 'name');
    CRM_Core_OptionValue::getValues(array('name' => 'event_repeat_exclude_dates_'.$this->_parentEventId), $optionValue);
    $excludeOptionValues = array();
    if (!empty($optionValue)) {
      foreach($optionValue as $key => $val) {
        $excludeOptionValues[$val['value']] = date('m/d/Y', strtotime($val['value']));
      }
      $this->_excludeDateInfo = $excludeOptionValues;
    }
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

    //Set Schedule Reminder Id
    if (property_exists($this->_scheduleReminderDetails, 'id')) {
      $this->_scheduleReminderId = $this->_scheduleReminderDetails->id;
    }
    //Always pass current event's start date by default
    $currentEventStartDate = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_id, 'start_date', 'id');
    list($defaults['repetition_start_date'], $defaults['repetition_start_date_time']) = CRM_Utils_Date::setDateDefaults($currentEventStartDate, 'activityDateTime');

    // Check if there is id for this event in Reminder table
    if ($this->_scheduleReminderId) {
      $defaults['repetition_frequency_unit'] = $this->_scheduleReminderDetails->repetition_frequency_unit;
      $defaults['repetition_frequency_interval'] = $this->_scheduleReminderDetails->repetition_frequency_interval;
      $defaults['start_action_condition'] = array_flip(explode(",",$this->_scheduleReminderDetails->start_action_condition));
      foreach($defaults['start_action_condition'] as $key => $val) {
        $val = 1;
        $defaults['start_action_condition'][$key] = $val;
      }
      list($defaults['repeat_event_start_date'], $defaults['repeat_event_start_date_time']) = CRM_Utils_Date::setDateDefaults($this->_parentEventStartDate, 'activityDateTime');
      $defaults['start_action_offset'] = $this->_scheduleReminderDetails->start_action_offset;
      if ($this->_scheduleReminderDetails->start_action_offset) {
        $defaults['ends'] = 1;
      }
      list($defaults['repeat_absolute_date']) = CRM_Utils_Date::setDateDefaults($this->_scheduleReminderDetails->absolute_date);
      if ($this->_scheduleReminderDetails->absolute_date) {
        $defaults['ends'] = 2;
      }
      $defaults['limit_to'] = $this->_scheduleReminderDetails->limit_to;
      if ($this->_scheduleReminderDetails->limit_to) {
        $defaults['repeats_by'] = 1;
      }
      $explodeStartActionCondition = array();
      if ($this->_scheduleReminderDetails->entity_status) {
        $explodeStartActionCondition = explode(" ", $this->_scheduleReminderDetails->entity_status);
        $defaults['entity_status_1'] = $explodeStartActionCondition[0];
        $defaults['entity_status_2'] = $explodeStartActionCondition[1];
      }
      if ($this->_scheduleReminderDetails->entity_status) {
        $defaults['repeats_by'] = 2;
      }
    }
    return $defaults;
  }

  public function buildQuickForm() {
    CRM_Core_Form_RecurringEntity::buildQuickForm($this);
  }

  public function postProcess() {
    if ($this->_id) {
      $params = $this->controller->exportValues($this->_name);
      $params['event_id'] = $this->_id;
      $params['parent_event_id']  = $this->_parentEventId;
      $params['parent_event_start_date'] = $this->_parentEventStartDate;
      $params['parent_event_end_date'] = $this->_parentEventEndDate;
      //Unset event id
      unset($params['id']);

      //Set Schedule Reminder id
      $params['id'] = $this->_scheduleReminderId;
      $url = 'civicrm/event/manage/repeat';
      $urlParams = "action=update&reset=1&id={$this->_id}";

      CRM_Core_Form_RecurringEntity::postProcess($params, 'event');
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
   * This function gets all columns from civicrm_action_schedule on the basis of event id
   *
   * @param int $eventId Event ID
   * @param string $used_for Specifies for which entity type it's used for
   *
   * @access public
   * @static
   *
   * @return object
   */
  static public function getReminderDetailsByEventId($eventId, $used_for) {
    if ($eventId) {
      $query = "
        SELECT *
        FROM   civicrm_action_schedule
        WHERE  entity_value = %1";
      if ($used_for) {
        $query .= " AND used_for = %2";
      }
      $params = array(
        1 => array($eventId, 'Integer'),
        2 => array($used_for, 'String')
      );
      $dao = CRM_Core_DAO::executeQuery($query, $params);
      $dao->fetch();
    }
    return $dao;
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
}
