<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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

require_once 'packages/When/When.php'; 

class CRM_Core_BAO_RecurringEntity extends CRM_Core_DAO_RecurringEntity {

  static $_tableDAOMapper = 
    array(
      'civicrm_event' => 'CRM_Event_DAO_Event',
      'civicrm_price_set_entity' => 'CRM_Price_DAO_PriceSetEntity',
      'civicrm_uf_join'     => 'CRM_Core_DAO_UFJoin',
      'civicrm_tell_friend' => 'CRM_Friend_DAO_Friend',
      'civicrm_pcp_block'   => 'CRM_PCP_DAO_PCPBlock',
    );

  static function add(&$params) {
    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::pre('edit', 'RecurringEntity', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'RecurringEntity', NULL, $params);
    }

    $daoRecurringEntity = new CRM_Core_DAO_RecurringEntity();
    $daoRecurringEntity->copyValues($params);
    $result = $daoRecurringEntity->save();

    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::post('edit', 'RecurringEntity', $daoRecurringEntity->id, $daoRecurringEntity);
    }
    else {
      CRM_Utils_Hook::post('create', 'RecurringEntity', $daoRecurringEntity->id, $daoRecurringEntity);
    }
    return $result;
  }

  static function quickAdd($parentId, $entityId, $entityTable) {
    $params = 
      array(
        'parent_id'    => $parentId,
        'entity_id'    => $entityId,
        'entity_table' => $entityTable
      );
    return self::add($params);
  }

  // MODE = 3 (ALL)
  static public function getEntitiesForParent($parentId, $entityTable, $includeParent = TRUE, $mode = 3, $initiatorId = NULL) {
    $entities = array();

    if (!$initiatorId) {
      $initiatorId = $parentId;
    } 

    $queryParams = array(
      1 => array($parentId,    'Integer'),
      2 => array($entityTable, 'String'),
      3 => array($initiatorId, 'Integer'),
    );

    if (!$mode) {
      $mode = CRM_Core_DAO::singleValueQuery("SELECT mode FROM civicrm_recurring_entity WHERE entity_id = %3 AND entity_table = %2", $queryParams);
    }

    $query = "SELECT *
      FROM civicrm_recurring_entity
      WHERE parent_id = %1 AND entity_table = %2";
    if (!$includeParent) {
      $query .= " AND entity_id != " . ($initiatorId ? "%3" : "%1");
    }

    if ($mode == '1') { // MODE = SINGLE
      $query .= " AND entity_id = %3";
    } else if ($mode == '2') { // MODE = FUTURE
      $recurringEntityID = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_recurring_entity WHERE entity_id = %3 AND entity_table = %2", $queryParams);
      if ($recurringEntityID) {
        $query .= $includeParent ? " AND id >= %4" : " AND id > %4";
        $query .= " ORDER BY id ASC"; // FIXME: change to order by dates  
        $queryParams[4] = array($recurringEntityID, 'Integer');
      } else {
        // something wrong, return empty
        return array();
      }
    }

    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $entities["{$dao->entity_table}_{$dao->entity_id}"]['table'] = $dao->entity_table;
      $entities["{$dao->entity_table}_{$dao->entity_id}"]['id'] = $dao->entity_id;
    }
    return $entities;
  }

  static public function getEntitiesFor($entityId, $entityTable, $includeParent = TRUE, $mode = 3) {
    $parentId = self::getParentFor($entityId, $entityTable);
    if ($parentId) {
      return self::getEntitiesForParent($parentId, $entityTable, $includeParent, $mode, $entityId);
    }
    return array();
  }

  static public function getParentFor($entityId, $entityTable, $includeParent = TRUE) {
    $query = "
      SELECT parent_id 
      FROM civicrm_recurring_entity
      WHERE entity_id = %1 AND entity_table = %2";
    if (!$includeParent) {
      $query .= " AND parent_id != %1";
    }
    $parentId = 
      CRM_Core_DAO::singleValueQuery($query,
        array(
          1 => array($entityId, 'Integer'),
          2 => array($entityTable, 'String'),
        )
      );
    return $parentId;
  }

  //static public function copyCreateEntity('civicrm_event', array('id' => $params['parent_event_id'], $newParams) {
  static public function copyCreateEntity($entityTable, $fromCriteria, $newParams, $createRecurringEntity = TRUE) {
    $daoName = self::$_tableDAOMapper[$entityTable];
    $newObject = CRM_Core_DAO::copyGeneric($daoName, $fromCriteria, $newParams);

    if ($newObject->id && $createRecurringEntity) {
      $object = new $daoName( );
      foreach ($fromCriteria as $key => $value) {
        $object->$key = $value;
      }
      $object->find(TRUE);

      CRM_Core_BAO_RecurringEntity::quickAdd($object->id, $newObject->id, $entityTable);
    }
    return $newObject;
  }

  static public function triggerUpdate($obj) {
    // if DB version is earlier than 4.6 skip any processing
    static $currentVer = NULL;
    if (!$currentVer) {
      $currentVer = CRM_Core_BAO_Domain::version();
    }
    if (version_compare($currentVer, '4.6.alpha1') < 0) {
      return;
    }

    static $processedEntities = array();
    if (empty($obj->id) || empty($obj->__table)) {
      return FALSE;
    }
    $key = "{$obj->__table}_{$obj->id}";

    if (array_key_exists($key, $processedEntities)) {
      // already processed
      return NULL;
    }

    // get related entities
    $repeatingEntities = self::getEntitiesFor($obj->id, $obj->__table, FALSE, NULL);
    if (empty($repeatingEntities)) {
      // return if its not a recurring entity parent
      return NULL;
    }
    // mark being processed
    $processedEntities[$key] = 1;

    // to make sure we not copying to source itself
    unset($repeatingEntities[$key]);

    foreach($repeatingEntities as $key => $val) {
      $entityID = $val['id'];
      $entityTable = $val['table'];

      $processedEntities[$key] = 1;

      if (array_key_exists($entityTable, self::$_tableDAOMapper)) {
        $daoName = self::$_tableDAOMapper[$entityTable];

        // FIXME: generalize me
        $skipData = array('start_date' => NULL, 
          'end_date' => NULL,
        );

        $updateDAO = CRM_Core_DAO::cascadeUpdate($daoName, $obj->id, $entityID, $skipData);
        CRM_Core_DAO::freeResult();
      }
    }
    // done with processing. lets unset static var.
    unset($processedEntities);
  }

  static function mapFormValuesToDB($formParams = array()){   
    $dbParams = array();
    if(CRM_Utils_Array::value('used_for', $formParams)){
      $dbParams['used_for'] = $formParams['used_for'];
    }
	
    if(CRM_Utils_Array::value('parent_event_id', $formParams)){
        $dbParams['entity_value'] = $formParams['parent_event_id'];
      }

    if(CRM_Utils_Array::value('repetition_frequency_unit', $formParams)){
        $dbParams['repetition_frequency_unit'] = $formParams['repetition_frequency_unit'];
      }

    if(CRM_Utils_Array::value('repetition_frequency_interval', $formParams)){
        $dbParams['repetition_frequency_interval'] = $formParams['repetition_frequency_interval'];
      }

    //For Repeats on:(weekly case)
      if($formParams['repetition_frequency_unit'] == 'week'){
        if(CRM_Utils_Array::value('start_action_condition', $formParams)){
          $repeats_on = CRM_Utils_Array::value('start_action_condition', $formParams);
          $dbParams['start_action_condition'] = implode(",", array_keys($repeats_on));
        }
      }

    //For Repeats By:(monthly case)
      if($formParams['repetition_frequency_unit'] == 'month'){
        if($formParams['repeats_by'] == 1){
          if(CRM_Utils_Array::value('limit_to', $formParams)){
            $dbParams['limit_to'] = $formParams['limit_to'];
          }
        }
        if($formParams['repeats_by'] == 2){
          if(CRM_Utils_Array::value('start_action_date_1', $formParams) && CRM_Utils_Array::value('start_action_date_2', $formParams)){
            $dbParams['start_action_date'] = $formParams['start_action_date_1']." ".$formParams['start_action_date_2'];
          }
        }
      }

    //For "Ends" - After: 
      if($formParams['ends'] == 1){
        if(CRM_Utils_Array::value('start_action_offset', $formParams)){
          $dbParams['start_action_offset'] = $formParams['start_action_offset'];
        }
      }

      //For "Ends" - On: 
      if($formParams['ends'] == 2){
        if(CRM_Utils_Array::value('repeat_absolute_date', $formParams)){
          $dbParams['absolute_date'] = CRM_Utils_Date::processDate($formParams['repeat_absolute_date']);
        }
      }
      return $dbParams;
    }

    static public function getScheduleReminderDetailsById($scheduleReminderId){
      $query = "SELECT *
                FROM civicrm_action_schedule WHERE 1";
      if($scheduleReminderId){
        $query .= "
        AND id = %1";
      }
      $dao = CRM_Core_DAO::executeQuery($query,
            array(
              1 => array($scheduleReminderId, 'Integer')
            )
          );
      $dao->fetch();
      return $dao;
    }
    
    static function getRecursionFromReminder($scheduleReminderId){
      if($scheduleReminderId){
        //Get all the details from schedule reminder table
        $scheduleReminderDetails = self::getScheduleReminderDetailsById($scheduleReminderId);
        $scheduleReminderDetails = (array) $scheduleReminderDetails;
        $recursionDetails = self::getRecursionFromReminderByDBParams($scheduleReminderDetails);
      }
      return $recursionDetails;
    }
    
    static function getRecursionFromReminderByDBParams($scheduleReminderDetails = array()){
      $r = new When();
      //If there is some data for this id
      if($scheduleReminderDetails['repetition_frequency_unit']){
        $currDate = date("Y-m-d H:i:s");
        $start = new DateTime($currDate);
        if($scheduleReminderDetails['repetition_frequency_unit']){
          $repetition_frequency_unit = $scheduleReminderDetails['repetition_frequency_unit'];
          if($repetition_frequency_unit == "day"){
            $repetition_frequency_unit = "dai";
          }
          $repetition_frequency_unit = $repetition_frequency_unit.'ly';
          $r->recur($start, $repetition_frequency_unit);
        }

        if($scheduleReminderDetails['repetition_frequency_interval']){
          $r->interval($scheduleReminderDetails['repetition_frequency_interval']);
        }else{
          $r->errors[] = 'Repeats every: is a required field';
        }

        //week
        if($scheduleReminderDetails['repetition_frequency_unit'] == 'week'){
          if($scheduleReminderDetails['start_action_condition']){
            $startActionCondition = $scheduleReminderDetails['start_action_condition'];
            $explodeStartActionCondition = explode(',', $startActionCondition);
            $buildRuleArray = array();
            foreach($explodeStartActionCondition as $key => $val){
              $buildRuleArray[] = strtoupper(substr($val, 0, 2));
            }
            $r->wkst('MO')->byday($buildRuleArray);
          }
        }

        //month 
        if($scheduleReminderDetails['repetition_frequency_unit'] == 'month'){
          if($scheduleReminderDetails['limit_to']){
            $r->bymonthday(array($scheduleReminderDetails['limit_to']));
          }
          if($scheduleReminderDetails['start_action_date']){
            $startActionDate = explode(" ", $scheduleReminderDetails['start_action_date']);
            switch ($startActionDate[0]) {
              case 'first':
                  $startActionDate1 = 1;
                  break;
              case 'second':
                  $startActionDate1 = 2;
                  break;
              case 'third':
                  $startActionDate1 = 3;
                  break;
              case 'fourth':
                  $startActionDate1 = 4;
                  break;
              case 'last':
                  $startActionDate1 = -1;
                  break;
            }
            $concatStartActionDateBits = $startActionDate1.strtoupper(substr($startActionDate[1], 0, 2));
            $r->byday(array($concatStartActionDateBits));
          }
        }

        //Ends
        if($scheduleReminderDetails['start_action_offset']){
          if($scheduleReminderDetails['start_action_offset'] > 30){
            $r->errors[] = 'Occurrences should be less than or equal to 30';
          }
          $r->count($scheduleReminderDetails['start_action_offset']);
        }

        if($scheduleReminderDetails['absolute_date']){
          $absoluteDate = CRM_Utils_Date::setDateDefaults($scheduleReminderDetails['absolute_date']);
          $endDate = new DateTime($absoluteDate[0].' '.$absoluteDate[1]);
          $r->until($endDate);
        }

        if(!$scheduleReminderDetails['start_action_offset'] && !$scheduleReminderDetails['absolute_date']){
          $r->errors[] = 'Ends: is a required field';
        }
     }else{
       $r->errors[] = 'Repeats: is a required field';
     }
      return $r;
    }
    
  /*
   * Get Reminder id based on event id
   */
  static public function getReminderDetailsByEventId($eventId, $used_for){
    if($eventId){
      $query = "
        SELECT *
        FROM   civicrm_action_schedule 
        WHERE  entity_value = %1";
      if($used_for){
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
  
  static public function generateRecursions($recursionObj, $params = array(), $excludeDates = array()) { 
    $newParams = $recursionResult = array();
    if ($recursionObj && !empty($params)) { 
      $initialCount = CRM_Utils_Array::value('start_action_offset', $params);
      if(CRM_Utils_Array::value('parent_event_start_date', $params) && CRM_Utils_Array::value('parent_event_id', $params)){
        $count = 1;
        while ($result = $recursionObj->next()) {
          $newParams['start_date'] = CRM_Utils_Date::processDate($result->format('Y-m-d H:i:s'));
          $parentStartDate = new DateTime($params['parent_event_start_date']);

          //If events with end date
          if(CRM_Utils_Array::value('parent_event_end_date', $params)){
            $parentEndDate = new DateTime($params['parent_event_end_date']);
            $interval = $parentStartDate->diff($parentEndDate);
            $end_date = new DateTime($newParams['start_date']);
            $end_date->add($interval);
            $newParams['end_date'] = CRM_Utils_Date::processDate($end_date->format('Y-m-d H:i:s'));
            $recursionResult[$count]['end_date'] = $newParams['end_date'];
          }
          $recursionResult[$count]['start_date'] = $newParams['start_date'];

          $skip = FALSE;
          foreach ($excludeDates as $date) {
            $date = CRM_Utils_Date::processDate($date, NULL, FALSE, 'Ymd');
            if (($date == $result->format('Ymd')) || 
              ($end_date && ($date > $result->format('Ymd')) && ($date <= $end_date->format('Ymd')))
            ) {
                $skip = TRUE;
                break;
            }
          }

          if ($skip) {
            unset($recursionResult[$count]);
            if ($initialCount && ($initialCount > 0)) {
              // lets increase the counter, so we get correct number of occurrences
              $initialCount++;
              $recursionObj->count($initialCount);
            }
            continue;
          }
          $count++;
        }
      }
    }
    return $recursionResult;
  }
  
  static public function addEntityThroughRecursion($recursionResult = array(), $currEntityID){
    if(!empty($recursionResult) && $currEntityID){
      $parent_event_id = CRM_Core_BAO_RecurringEntity::getParentFor($currEntityID, 'civicrm_event');
      if(!$parent_event_id){
        $parent_event_id = $currEntityID;
      }

      // add first entry just for parent
      CRM_Core_BAO_RecurringEntity::quickAdd($parent_event_id, $parent_event_id, 'civicrm_event');

      foreach ($recursionResult as $key => $value) {
        $newEventObj = CRM_Core_BAO_RecurringEntity::copyCreateEntity('civicrm_event', 
        array('id' => $parent_event_id), 
        $value);

        CRM_Core_BAO_RecurringEntity::copyCreateEntity('civicrm_price_set_entity', 
          array(
            'entity_id' => $parent_event_id, 
            'entity_table' => 'civicrm_event'
          ), 
          array(
            'entity_id' => $newEventObj->id
          ),
          FALSE
        );

        CRM_Core_BAO_RecurringEntity::copyCreateEntity('civicrm_uf_join', 
          array(
            'entity_id' => $parent_event_id, 
            'entity_table' => 'civicrm_event'
          ), 
          array(
            'entity_id' => $newEventObj->id
          ),
          FALSE
        );

        CRM_Core_BAO_RecurringEntity::copyCreateEntity('civicrm_tell_friend', 
          array(
            'entity_id' => $parent_event_id, 
            'entity_table' => 'civicrm_event'
          ), 
          array(
            'entity_id' => $newEventObj->id
          )
        );

        CRM_Core_BAO_RecurringEntity::copyCreateEntity('civicrm_pcp_block', 
          array(
            'entity_id' => $parent_event_id, 
            'entity_table' => 'civicrm_event'
          ), 
          array(
            'entity_id' => $newEventObj->id
          )
        );
      }
    }
  }
  
  static public function delEntityRelations($entityId, $entityTable){
    if(!$entityId && !$entityTable){
      return FALSE;
    }
    $parentID = self::getParentFor($entityId, $entityTable);
    if($parentID){
      $dao = new CRM_Core_DAO_RecurringEntity();
      $dao->parent_id = $parentID;
      return $dao->delete();
    }
  }
    
}
