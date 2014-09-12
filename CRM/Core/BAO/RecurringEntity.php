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

  protected $schedule = array();
  protected $scheduleId = NULL;
  protected $scheduleDBParams = array();

  protected $dateColumns = array();
  protected $overwriteColumns = array();
  protected $intervalDateColumns = array();

  protected $excludeDates = array();

  protected $recursion = NULL;

  protected $isGenRecurringEntity = TRUE;

  static $_tableDAOMapper = 
    array(
      'civicrm_event'       => 'CRM_Event_DAO_Event',
      'civicrm_price_set_entity' => 'CRM_Price_DAO_PriceSetEntity',
      'civicrm_uf_join'     => 'CRM_Core_DAO_UFJoin',
      'civicrm_tell_friend' => 'CRM_Friend_DAO_Friend',
      'civicrm_pcp_block'   => 'CRM_PCP_DAO_PCPBlock',
      'civicrm_activity'    => 'CRM_Activity_DAO_Activity',
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

  function entityId($entityId) {
    $this->entity_id = $entityId;
  }

  function entityTable($entityTable) {
    $this->entity_table = $entityTable;
  }

  function dateColumns($dateColumns = array()) {
    $this->dateColumns = $dateColumns;
  }

  function scheduleDBParams($scheduleDBParams = array()) {
    $this->scheduleDBParams = $scheduleDBParams;
  }

  function setMode($mode) {
    $this->mode = $mode;
    $this->parent_id = $this->entity_id;
    $this->save();
  }

  // generate all new entities based on object vars
  function generate() {
    // fixme: check if entityid & entitytable set
    $entities = array();

    if ($this->scheduleId) {
      // get params by ID
      $this->recursion = $this->getRecursionFromReminder($this->scheduleId);
    } else if (!empty($this->schedule)) {
      $this->scheduleDBParams = $this->mapFormValuesToDB($this->schedule);//call using obj
    }
    CRM_Core_Error::debug_var('$this->scheduleDBParams', $this->scheduleDBParams);
    if (!empty($this->scheduleDBParams)) {
      $this->recursion = $this->getRecursionFromReminderByDBParams($this->scheduleDBParams);
    }
    //CRM_Core_Error::debug_var('$this->recursion', $this->recursion);

    if (!empty($this->recursion)) {
      $this->recursionDates = $this->generateRecursions2();
      CRM_Core_Error::debug_var('$this->recursionDates1', $this->recursionDates);

      // save an entry with initiating entity-id & entity-table
      if (!$this->find(TRUE)) {
        $this->parent_id = $this->entity_id;
        $this->save();
      }
      CRM_Core_Error::debug_var('$this', $this);

      // generate new DAOs and along with entries in recurring_entity table
      $entities = $this->copyCreateEntities();
    }
    CRM_Core_Error::debug_var('$entities', $entities);
    return $entities;
  }

  function copyCreateEntities() {
    CRM_Core_Error::debug_var('$this->recursionDates post save', $this->recursionDates);
    $newEntities = array();
    foreach ($this->recursionDates as $key => $date) {
      $newCriteria = array();
      foreach ($this->dateColumns as $col) {
        //$newCriteria[$col] = $date;
        $newCriteria[$col] = '20121230';
        //$newCriteria[$col] = CRM_Utils_Date::isoToMysql($date);
      }
      foreach ($this->overwriteColumns as $col => $val) {
        $newCriteria[$col] = $val;
      }
      CRM_Core_Error::debug_var('$newCriteria', $newCriteria);
      $obj = CRM_Core_BAO_RecurringEntity::copyCreateEntity($this->entity_table, 
        array('id' => $this->entity_id), 
        $newCriteria,
        $this->isGenRecurringEntity
      );
      $newEntities[] = $obj->id;
    }
    return $newEntities;
  }

  function generateRecursions2() {
    $newParams = $recursionResult = array();
    if (is_a($this->recursion, 'When')) { 
      $initialCount = CRM_Utils_Array::value('start_action_offset', $this->scheduleDBParams);
      $interval     = CRM_Utils_Array::value('interval',    $this->scheduleDBParams);

      $count = 1;
      while ($result = $this->recursion->next()) {
        $recursionResult[$count] = CRM_Utils_Date::processDate($result->format('Y-m-d H:i:s'));

        $skip = FALSE;
        foreach ($this->excludeDates as $date) {
          $date = CRM_Utils_Date::processDate($date, NULL, FALSE, 'Ymd');
          if ($date == $result->format('Ymd')) {
            $skip = TRUE;
            break;
          }
        }

        if ($skip) {
          unset($recursionResult[$count]);
          if ($initialCount && ($initialCount > 0)) {
            // lets increase the counter, so we get correct number of occurrences
            $initialCount++;
            $this->recursion->count($initialCount);
          }
          continue;
        }
        $count++;
      }
    }
    return $recursionResult;
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
      } else {
        CRM_Core_Error::fatal("DAO Mapper missing for $entityTable.");
      }
    }
    // done with processing. lets unset static var.
    unset($processedEntities);
  }

  function mapFormValuesToDB($formParams = array()){   
    $dbParams = array();
    if(CRM_Utils_Array::value('used_for', $formParams)){
      $dbParams['used_for'] = $formParams['used_for'];
    }

    if(CRM_Utils_Array::value('parent_event_id', $formParams)){
      $dbParams['entity_value'] = $formParams['parent_event_id'];
    }

    if(CRM_Utils_Array::value('repetition_start_date', $formParams) &&
      CRM_Utils_Array::value('repetition_start_date_time', $formParams)){
        $repetition_start_date = new DateTime($formParams['repetition_start_date']." ".$formParams['repetition_start_date_time']);
        $repetition_start_date->modify('+1 day');
        $dbParams['entity_status'] = CRM_Utils_Date::processDate($repetition_start_date->format('Y-m-d H:i:s'));
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

  function getRecursionFromReminder($scheduleReminderId){
    if($scheduleReminderId){
      //Get all the details from schedule reminder table
      $scheduleReminderDetails = self::getScheduleReminderDetailsById($scheduleReminderId);
      $scheduleReminderDetails = (array) $scheduleReminderDetails;
      $recursionDetails = self::getRecursionFromReminderByDBParams($scheduleReminderDetails);
    }
    return $recursionDetails;
  }

  function getRecursionFromReminderByDBParams($scheduleReminderDetails = array()){
    $r = new When();
    //If there is some data for this id
    if($scheduleReminderDetails['repetition_frequency_unit']){
      if($scheduleReminderDetails['entity_status']){
        $currDate = date('Y-m-d H:i:s', strtotime($scheduleReminderDetails['entity_status']));
      }else{
        $currDate = date("Y-m-d H:i:s");
      }
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
        }else if($scheduleReminderDetails['limit_to']){
          $r->bymonthday(array($scheduleReminderDetails['limit_to']));
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

  static public function getInterval($startDate, $endDate) { 
    if ($startDate && $endDate) {
      $startDate = new DateTime($startDate);
      $endDate   = new DateTime($endDate);

      return $startDate->diff($endDate);
    }
  }

  static public function generateRecursions($recursionObj, $params = array(), $excludeDates = array()) { 
    $newParams = $recursionResult = array();
    if (is_a($recursionObj, 'When')) { 
      $initialCount = CRM_Utils_Array::value('start_action_offset', $params);
      $interval     = CRM_Utils_Array::value('interval',    $params);

      $count = 1;
      while ($result = $recursionObj->next()) {
        $recursionResult[$count]['start_date'] = CRM_Utils_Date::processDate($result->format('Y-m-d H:i:s'));

        if($interval){
          $endDate = new DateTime($recursionResult[$count]['start_date']);
          $endDate->add($interval);
          $recursionResult[$count]['end_date'] = CRM_Utils_Date::processDate($endDate->format('Y-m-d H:i:s'));
        }

        $skip = FALSE;
        foreach ($excludeDates as $date) {
          $date = CRM_Utils_Date::processDate($date, NULL, FALSE, 'Ymd');
          if (($date == $result->format('Ymd')) || 
            ($endDate && ($date > $result->format('Ymd')) && ($date <= $endDate->format('Ymd')))
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
    return $recursionResult;
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

  static public function getParticipantCountforEvent($listOfRelatedEntities = array()){
    if(!empty($listOfRelatedEntities)){
      $implodeRelatedEntities = implode(',', array_map(function($entity){
        return $entity['id'];
      }, $listOfRelatedEntities));
      if($implodeRelatedEntities){
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

  static function testActivityGeneration() {
    //Activity set initial params
    $daoActivity = new CRM_Activity_DAO_Activity();
    $daoActivity->activity_type_id = 1;
    $daoActivity->subject = "Initial Activity";
    $daoActivity->activity_date_time = date('YmdHis');
    $daoActivity->save();
 
    $recursion = new CRM_Core_BAO_RecurringEntity();
    $recursion->entityId($daoActivity->id);
    $recursion->entityTable('civicrm_activity');
    $recursion->dateColumns(array('activity_date_time'));
    $recursion->scheduleDBParams(array(
      'entity_value'      => $daoActivity->id,
      'entity_status'     => $daoActivity->activity_date_time,
      'start_action_date' => 'fourth saturday',
      'repetition_frequency_unit' => 'month',
      'repetition_frequency_interval' => 3,
      'start_action_offset' => 5,
      //'used_for' => 'activity'
    ));

    // skip copying these column when creating new daos
    // or populate with values provided here
    //$recursion->overwriteColumns = array(); 
    //$recursion->intervalDateColumns = array('end_date' => '1 day'); 

    $generatedEntities = $recursion->generate(); 

    // try changing something
    $recursion->setMode(3); // sets ->mode var & saves in DB

    // lets change subject of initial activity that we created in begining
    $daoActivity->find(TRUE);
    $daoActivity->subject = 'I changed it';
    $daoActivity->save();
  }
}
