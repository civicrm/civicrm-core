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

  public $schedule = array();
  public $scheduleId = NULL;
  public $scheduleFormValues = array();

  public $dateColumns = array();
  public $overwriteColumns = array();
  public $intervalDateColumns = array();
  public $excludeDates = array();
  
  public $linkedEntities = array();

  public $isRecurringEntityRecord = TRUE;

  protected $recursion = NULL;

  static $_tableDAOMapper = 
    array(
      'civicrm_event'       => 'CRM_Event_DAO_Event',
      'civicrm_price_set_entity' => 'CRM_Price_DAO_PriceSetEntity',
      'civicrm_uf_join'     => 'CRM_Core_DAO_UFJoin',
      'civicrm_tell_friend' => 'CRM_Friend_DAO_Friend',
      'civicrm_pcp_block'   => 'CRM_PCP_DAO_PCPBlock',
      'civicrm_activity'    => 'CRM_Activity_DAO_Activity',
      'civicrm_activity_contact'  => 'CRM_Activity_DAO_ActivityContact',
    );

  static $_updateSkipFields = 
    array(
      'civicrm_event'       => array('start_date', 'end_date'),
      'civicrm_tell_friend' => array('entity_id'),
      'civicrm_pcp_block'   => array('entity_id'),
      'civicrm_activity'    => array('activity_date_time'),
    );

  static $_linkedEntitiesInfo = 
    array(
      'civicrm_tell_friend' => array(
        'entity_id_col'    => 'entity_id', 
        'entity_table_col' => 'entity_table'
      ),
      'civicrm_price_set_entity' => array(
        'entity_id_col'    => 'entity_id', 
        'entity_table_col' => 'entity_table',
        'is_multirecord'   => TRUE,
      ),
      'civicrm_uf_join' => array(
        'entity_id_col'    => 'entity_id', 
        'entity_table_col' => 'entity_table'
      ),
      'civicrm_pcp_block' => array(
        'entity_id_col'    => 'entity_id', 
        'entity_table_col' => 'entity_table'
      ),
    );
  
  /**
   * Function to save records in civicrm_recujrring_entity table
   *
   * @param array $params reference array contains the values submitted by the form
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params) {
    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::pre('edit', 'RecurringEntity', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'RecurringEntity', NULL, $params);
    }

    $daoRecurringEntity = new CRM_Core_DAO_RecurringEntity();
    $daoRecurringEntity->copyValues($params);
    $daoRecurringEntity->find(TRUE);
    $result = $daoRecurringEntity->save();

    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::post('edit', 'RecurringEntity', $daoRecurringEntity->id, $daoRecurringEntity);
    }
    else {
      CRM_Utils_Hook::post('create', 'RecurringEntity', $daoRecurringEntity->id, $daoRecurringEntity);
    }
    return $result;
  }

  /**
   * Wrapper for the function add() to add entry in recurring entity
   *
   * @param int $parentId Parent entity id
   * @param int $entityId Child entity id
   * @param String $entityTable Name of the entity table
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function quickAdd($parentId, $entityId, $entityTable) {
    $params = 
      array(
        'parent_id'    => $parentId,
        'entity_id'    => $entityId,
        'entity_table' => $entityTable
      );
    return self::add($params);
  }

  /**
   * This function updates the mode column in the civicrm_recurring_entity table
   *
   * @param int $mode Mode of the entity to cascade changes across parent/child relations eg 1 - only this entity, 2 - this and the following entities, 3 - All the entity
   *
   * @access public
   *
   * @return void
   */
  function mode($mode) {
    if ($this->entity_id && $this->entity_table) {
      if ($this->find(TRUE)) {
        $this->mode = $mode;
      }
      else {
        $this->parent_id = $this->entity_id;
        $this->mode = $mode;
      }
      $this->save();
    }
  }

  /**
   * This function generates all new entities based on object vars
   * 
   * @return array
   */
  function generate() {
    $this->generateRecursiveDates();

    return $this->generateEntities();
  }

  /**
   * This function builds a "When" object based on schedule/reminder params
   * 
   * @return object When object
   */
  function generateRecursion() {
    // return if already generated
    if (is_a($this->recursion, 'When')) { 
      return $this->recursion;
    }

    if ($this->scheduleId) {
      // get params by ID
      $this->schedule = $this->getScheduleParams($this->scheduleId);
    }
    else if (!empty($this->scheduleFormValues)) {
      $this->schedule = $this->mapFormValuesToDB($this->scheduleFormValues);
    }

    if (!empty($this->schedule)) {
      $this->recursion = $this->getRecursionFromSchedule($this->schedule);
    }
    return $this->recursion;
  }

  /**
   * Generate new DAOs and along with entries in civicrm_recurring_entity table
   * 
   * @return array
   */
  function generateEntities() {
    $newEntities  = array();
    $findCriteria = array();
    if (!empty($this->recursionDates)) {
      if ($this->entity_id) {
        $findCriteria = array('id' => $this->entity_id);

        // save an entry with initiating entity-id & entity-table
        if ($this->entity_table && !$this->find(TRUE)) {
          $this->parent_id = $this->entity_id;
          $this->save();
        }
      }
      if (empty($findCriteria)) {
        CRM_Core_Error::fatal("Find criteria missing to generate from. Make sure entity_id and table is set.");
      }

      $count = 0;
      foreach ($this->recursionDates as $key => $dateCols) {
        $newCriteria = $dateCols;
        foreach ($this->overwriteColumns as $col => $val) {
          $newCriteria[$col] = $val;
        }
        // create main entities
        $obj = CRM_Core_BAO_RecurringEntity::copyCreateEntity($this->entity_table, 
          $findCriteria,
          $newCriteria,
          $this->isRecurringEntityRecord
        );

        if (is_a($obj, 'CRM_Core_DAO') && $obj->id) {
          $newCriteria = array();
          $newEntities[$this->entity_table][$count] = $obj->id;

          foreach ($this->linkedEntities as $linkedInfo) {
            foreach ($linkedInfo['linkedColumns'] as $col) {
              $newCriteria[$col] = $obj->id;
            }
            // create linked entities
            $linkedObj = CRM_Core_BAO_RecurringEntity::copyCreateEntity($linkedInfo['table'], 
              $linkedInfo['findCriteria'],
              $newCriteria,
              CRM_Utils_Array::value('isRecurringEntityRecord', $linkedInfo, TRUE)
            );

            if (is_a($linkedObj, 'CRM_Core_DAO') && $linkedObj->id) {
              $newEntities[$linkedInfo['table']][$count] = $linkedObj->id;
            }
          }
        }
        $count++;
      }
    }

    return $newEntities;
  }

  /**
   * This function iterates through when object criterias and 
   * generates recursive dates based on that
   * 
   * @return array array of dates
   */
  function generateRecursiveDates() {
    $this->generateRecursion();

    $recursionDates = array();
    if (is_a($this->recursion, 'When')) { 
      $initialCount = CRM_Utils_Array::value('start_action_offset', $this->schedule);

      $exRangeStart = $exRangeEnd = NULL;
      if (!empty($this->excludeDateRangeColumns)) {
        $exRangeStart = $this->excludeDateRangeColumns[0];
        $exRangeEnd   = $this->excludeDateRangeColumns[1];
      }

      $count = 1;
      while ($result = $this->recursion->next()) {
        $baseDate = CRM_Utils_Date::processDate($result->format('Y-m-d H:i:s'));

        foreach ($this->dateColumns as $col) {
          $recursionDates[$count][$col] = $baseDate;
        }
        foreach ($this->intervalDateColumns as $col => $interval) {
          $newDate = new DateTime($baseDate);
          $newDate->add($interval);
          $recursionDates[$count][$col] = CRM_Utils_Date::processDate($newDate->format('Y-m-d H:i:s'));
        }
        if ($exRangeStart) {
          $exRangeStartDate = CRM_Utils_Date::processDate($recursionDates[$count][$exRangeStart], NULL, FALSE, 'Ymd');
          $exRangeEndDate   = CRM_Utils_Date::processDate($recursionDates[$count][$exRangeEnd], NULL, FALSE, 'Ymd');
        }

        $skip = FALSE;
        foreach ($this->excludeDates as $exDate) {
          $exDate = CRM_Utils_Date::processDate($exDate, NULL, FALSE, 'Ymd');
          if (!$exRangeStart) {
            if ($exDate == $result->format('Ymd')) {
              $skip = TRUE;
              break;
            }
          }
          else {
            if (($exDate == $exRangeStartDate) ||
              ($exRangeEndDate && ($exDate > $exRangeStartDate) && ($exDate <= $exRangeEndDate))
            ) {
              $skip = TRUE;
              break;
            }
          }
        }

        if ($skip) {
          unset($recursionDates[$count]);
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
    $this->recursionDates = $recursionDates;

    return $recursionDates;
  }

  /**
   * This function gets all the children for a particular parent entity
   * 
   * @param int $parentId Parent entity id
   * @param string $entityTable Name of the entity table
   * @param boolean $includeParent If true parent id is included in result set and vice versa
   * @param int $mode 1. retrieve only one entity. 2. retrieve all future entities in the repeating set. 3. all entities in the repeating set.
   * @param int $initiatorId the instance where this function is invoked from
   * 
   * @access public
   * @static
   * 
   * @return array an array of child ids
   */
  static public function getEntitiesForParent($parentId, $entityTable, $includeParent = TRUE, $mode = 3, $initiatorId = NULL) {
    $entities = array();
    if (empty($parentId) || empty($entityTable)) {
      return $entities;
    }

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
    }
    else if ($mode == '2') { // MODE = FUTURE
      $recurringEntityID = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_recurring_entity WHERE entity_id = %3 AND entity_table = %2", $queryParams);
      if ($recurringEntityID) {
        $query .= $includeParent ? " AND id >= %4" : " AND id > %4";
        $query .= " ORDER BY id ASC"; // FIXME: change to order by dates  
        $queryParams[4] = array($recurringEntityID, 'Integer');
      }
      else {
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

  /**
   * This function when passed an entity id checks if it has parent and 
   * returns all other entities that are connected to same parent.
   * 
   * @param int $entityId entity id
   * @param string $entityTable Entity table name
   * @param boolean $includeParent Include parent in result set
   * @param int $mode 1. retrieve only one entity. 2. retrieve all future entities in the repeating set. 3. all entities in the repeating set.
   * 
   * @access public
   * @static
   * 
   * @return array array of connected ids
   */
  static public function getEntitiesFor($entityId, $entityTable, $includeParent = TRUE, $mode = 3) {
    $parentId = self::getParentFor($entityId, $entityTable);
    if ($parentId) {
      return self::getEntitiesForParent($parentId, $entityTable, $includeParent, $mode, $entityId);
    }
    return array();
  }

  /**
   * This function gets the parent for the entity id passed to it
   * 
   * @param int $entityId entity ID
   * @param string $entityTable Entity table name
   * @param boolean $includeParent Include parent in result set
   * 
   * @access public
   * @static
   * 
   * @return int unsigned $parentId Parent ID 
   */
  static public function getParentFor($entityId, $entityTable, $includeParent = TRUE) {
    if (empty($entityId) || empty($entityTable)) {
      return NULL;
    }

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

  /**
   * This function copies the information from parent entity and creates other entities with same information
   * 
   * @param string $entityTable Entity table name
   * @param array $fromCriteria array of all the fields & values on which basis to copy
   * @param array $newParams  array of all the fields & values to be copied besides the other fields
   * @param boolean $createRecurringEntity if to create a record in recurring_entity table
   * 
   * @access public
   * @static
   * 
   * @return object
   */
  static public function copyCreateEntity($entityTable, $fromCriteria, $newParams, $createRecurringEntity = TRUE) {
    $daoName = self::$_tableDAOMapper[$entityTable];
    if (!$daoName) {
      CRM_Core_Error::fatal("DAO Mapper missing for $entityTable.");
    }
    $newObject = CRM_Core_DAO::copyGeneric($daoName, $fromCriteria, $newParams);

    if (is_a($newObject, 'CRM_Core_DAO') && $newObject->id && $createRecurringEntity) {
      $object = new $daoName( );
      foreach ($fromCriteria as $key => $value) {
        $object->$key = $value;
      }
      $object->find(TRUE);

      CRM_Core_BAO_RecurringEntity::quickAdd($object->id, $newObject->id, $entityTable);
    }
    return $newObject;
  }

  /**
   * This function acts as a listener to dao->update whenever there is an update,
   * and propagates any changes to all related entities present in recurring entity table
   * 
   * @param object $event An object of /Civi/Core/DAO/Event/PostUpdate containing dao object that was just updated
   *   
   * @access public
   * @static
   *                          
   * @return void
   */
  static public function triggerUpdate($event) {
    // if DB version is earlier than 4.6 skip any processing
    static $currentVer = NULL;
    if (!$currentVer) {
      $currentVer = CRM_Core_BAO_Domain::version();
    }
    if (version_compare($currentVer, '4.6.alpha1') < 0) {
      return;
    }

    static $processedEntities = array();
    $obj =& $event->object;
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
        $daoName  = self::$_tableDAOMapper[$entityTable];

        $skipData = array();
        if (array_key_exists($entityTable, self::$_updateSkipFields)) {
          $skipFields = self::$_updateSkipFields[$entityTable];
          foreach ($skipFields as $sfield) {
            $skipData[$sfield] = NULL;
          }
        }

        $updateDAO = CRM_Core_DAO::cascadeUpdate($daoName, $obj->id, $entityID, $skipData);
        CRM_Core_DAO::freeResult();
      }
      else {
        CRM_Core_Error::fatal("DAO Mapper missing for $entityTable.");
      }
    }
    // done with processing. lets unset static var.
    unset($processedEntities);
  }

  /**
   * This function acts as a listener to dao->save,
   * and creates entries for linked entities in recurring entity table
   * 
   * @param object $event An object of /Civi/Core/DAO/Event/PostUpdate containing dao object that was just inserted
   *   
   * @access public
   * @static
   *                          
   * @return void
   */
  static public function triggerInsert($event) {
    $obj =& $event->object;
    if (!array_key_exists($obj->__table, self::$_linkedEntitiesInfo)) {
      return FALSE;
    }

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
      // already being processed. Exit recursive calls.
      return NULL;
    }

    // mark being processed
    $processedEntities[$key] = 1;

    // get related entities for table being saved
    $hasaRecurringRecord = self::getParentFor($obj->id, $obj->__table);

    if (empty($hasaRecurringRecord)) {
      // check if its a linked entity
      if (array_key_exists($obj->__table, self::$_linkedEntitiesInfo)) {
        $linkedDAO = new self::$_tableDAOMapper[$obj->__table]();
        $linkedDAO->id = $obj->id;
        if ($linkedDAO->find(TRUE)) {
          $idCol = self::$_linkedEntitiesInfo[$obj->__table]['entity_id_col'];
          $tableCol = self::$_linkedEntitiesInfo[$obj->__table]['entity_table_col'];

          $pEntityID    = $linkedDAO->$idCol;
          $pEntityTable = $linkedDAO->$tableCol;

          if (empty($pEntityID) || empty($pEntityTable)) {
            return NULL;
          }
          // find all parent recurring entity set
          $pRepeatingEntities = self::getEntitiesFor($pEntityID, $pEntityTable);

          if (!empty($pRepeatingEntities)) {
            // for each parent entity in the set, find out a similar linked entity,
            // if doesn't exist create one, and also create entries in recurring_entity table

            foreach($pRepeatingEntities as $key => $val) {
              if (array_key_exists($key, $processedEntities)) {
                // this graph is already being processed
                return NULL;
              }
              $processedEntities[$key] = 1;
            }

            // start with first entry with just itself
            CRM_Core_BAO_RecurringEntity::quickAdd($obj->id, $obj->id, $obj->__table);

            foreach($pRepeatingEntities as $key => $val) {
              $rlinkedDAO = new self::$_tableDAOMapper[$obj->__table]();
              $rlinkedDAO->$idCol = $val['id'];
              $rlinkedDAO->$tableCol = $val['table'];
              if ($rlinkedDAO->find(TRUE)) {
                CRM_Core_BAO_RecurringEntity::quickAdd($obj->id, $rlinkedDAO->id, $obj->__table);
              }
              else {
                // linked entity doesn't exist. lets create them
                $newCriteria = array(
                  $idCol    => $val['id'],
                  $tableCol => $val['table']
                );
                $linkedObj = CRM_Core_BAO_RecurringEntity::copyCreateEntity($obj->__table, 
                  array('id' => $obj->id),
                  $newCriteria,
                  TRUE
                );
                if ($linkedObj->id) {
                  CRM_Core_BAO_RecurringEntity::quickAdd($obj->id, $linkedObj->id, $obj->__table);
                }
              }
            }
          }
        }
      }
    }

    // done with processing. lets unset static var.
    unset($processedEntities);
  }

  /**
   * This function acts as a listener to dao->delete, and deletes an entry from recurring_entity table
   * 
   * @param object $event An object of /Civi/Core/DAO/Event/PostUpdate containing dao object that was just deleted
   *
   * @access public
   * @static
   *                          
   * @return void
   */
  static public function triggerDelete($event) {
    $obj =& $event->object;

    // if DB version is earlier than 4.6 skip any processing
    static $currentVer = NULL;
    if (!$currentVer) {
      $currentVer = CRM_Core_BAO_Domain::version();
    }
    if (version_compare($currentVer, '4.6.alpha1') < 0) {
      return;
    }

    static $processedEntities = array();
    if (empty($obj->id) || empty($obj->__table) || !$event->result) {
      return FALSE;
    }
    $key = "{$obj->__table}_{$obj->id}";

    if (array_key_exists($key, $processedEntities)) {
      // already processed
      return NULL;
    }

    // mark being processed
    $processedEntities[$key] = 1;

    $parentID = self::getParentFor($obj->id, $obj->__table, FALSE);
    if ($parentID) {
      $dao = new CRM_Core_DAO_RecurringEntity();
      $dao->entity_id = $obj->id;
      $dao->entity_table = $obj->__table;
      $dao->delete();
    }
  }

  /**
   * This function maps values posted from form to civicrm_action_schedule columns
   * 
   * @param array $formParams and array of form values posted
   * 
   * @return array 
   */
  function mapFormValuesToDB($formParams = array()) {   
    $dbParams = array();
    if (CRM_Utils_Array::value('used_for', $formParams)) {
      $dbParams['used_for'] = $formParams['used_for'];
    }

    if (CRM_Utils_Array::value('event_id', $formParams)) {
      $dbParams['entity_value'] = $formParams['event_id'];
    }

    if (CRM_Utils_Array::value('repetition_start_date', $formParams)) {
      $repetitionStartDate = $formParams['repetition_start_date'];
      if (CRM_Utils_Array::value('repetition_start_date_time', $formParams)) {
        $repetitionStartDate = $repetitionStartDate . " " . $formParams['repetition_start_date_time'];
      }
      $repetition_start_date = new DateTime($repetitionStartDate);
      $repetition_start_date->modify('+1 day');
      $dbParams['start_action_date'] = CRM_Utils_Date::processDate($repetition_start_date->format('Y-m-d H:i:s'));
    }

    if (CRM_Utils_Array::value('repetition_frequency_unit', $formParams)) {
      $dbParams['repetition_frequency_unit'] = $formParams['repetition_frequency_unit'];
    }

    if (CRM_Utils_Array::value('repetition_frequency_interval', $formParams)) {
      $dbParams['repetition_frequency_interval'] = $formParams['repetition_frequency_interval'];
    }

    //For Repeats on:(weekly case)
    if ($formParams['repetition_frequency_unit'] == 'week') {
      if (CRM_Utils_Array::value('start_action_condition', $formParams)) {
        $repeats_on = CRM_Utils_Array::value('start_action_condition', $formParams);
        $dbParams['start_action_condition'] = implode(",", array_keys($repeats_on));
      }
    }

    //For Repeats By:(monthly case)
    if ($formParams['repetition_frequency_unit'] == 'month') {
      if ($formParams['repeats_by'] == 1) {
        if (CRM_Utils_Array::value('limit_to', $formParams)) {
          $dbParams['limit_to'] = $formParams['limit_to'];
        }
      }
      if ($formParams['repeats_by'] == 2) {
        if (CRM_Utils_Array::value('entity_status_1', $formParams) && CRM_Utils_Array::value('entity_status_2', $formParams)) {
          $dbParams['entity_status'] = $formParams['entity_status_1']." ".$formParams['entity_status_2'];
        }
      }
    }

    //For "Ends" - After: 
    if ($formParams['ends'] == 1) {
      if (CRM_Utils_Array::value('start_action_offset', $formParams)) {
        $dbParams['start_action_offset'] = $formParams['start_action_offset'];
      }
    }

    //For "Ends" - On: 
    if ($formParams['ends'] == 2) {
      if (CRM_Utils_Array::value('repeat_absolute_date', $formParams)) {
        $dbParams['absolute_date'] = CRM_Utils_Date::processDate($formParams['repeat_absolute_date']);
      }
    }
    return $dbParams;
  }

  /**
   * This function gets all the columns of civicrm_action_schedule table based on id(primary key)
   * 
   * @param int $scheduleReminderId primary key of civicrm_action_schedule table
   * 
   * @access public
   * @static
   * 
   * @return object
   */
  static public function getScheduleReminderDetailsById($scheduleReminderId) {
    $query = "SELECT *
      FROM civicrm_action_schedule WHERE 1";
    if ($scheduleReminderId) {
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

  /**
   * This function is a wrapper of getScheduleReminderDetailsById function
   * 
   * @param int $scheduleReminderId primary key of civicrm_action_schedule table
   * 
   * @return array
   */
  function getScheduleParams($scheduleReminderId) {
    $scheduleReminderDetails = array();
    if ($scheduleReminderId) {
      //Get all the details from schedule reminder table
      $scheduleReminderDetails = self::getScheduleReminderDetailsById($scheduleReminderId);
      $scheduleReminderDetails = (array) $scheduleReminderDetails;
    }
    return $scheduleReminderDetails;
  }

  /**
   * This function takes criterias saved in civicrm_action_schedule table
   * and creates recursion rule
   * 
   * @param array $scheduleReminderDetails array of repeat criterias saved in civicrm_action_schedule table
   * 
   * @return object When object
   */
  function getRecursionFromSchedule($scheduleReminderDetails = array()) {
    $r = new When();
    //If there is some data for this id
    if ($scheduleReminderDetails['repetition_frequency_unit']) {
      if ($scheduleReminderDetails['start_action_date']) {
        $currDate = date('Y-m-d H:i:s', strtotime($scheduleReminderDetails['start_action_date']));
      }
      else {
        $currDate = date("Y-m-d H:i:s");
      }
      $start = new DateTime($currDate);
      if ($scheduleReminderDetails['repetition_frequency_unit']) {
        $repetition_frequency_unit = $scheduleReminderDetails['repetition_frequency_unit'];
        if ($repetition_frequency_unit == "day") {
          $repetition_frequency_unit = "dai";
        }
        $repetition_frequency_unit = $repetition_frequency_unit.'ly';
        $r->recur($start, $repetition_frequency_unit);
      }

      if ($scheduleReminderDetails['repetition_frequency_interval']) {
        $r->interval($scheduleReminderDetails['repetition_frequency_interval']);
      }
      else {
        $r->errors[] = 'Repeats every: is a required field';
      }

      //week
      if ($scheduleReminderDetails['repetition_frequency_unit'] == 'week') {
        if ($scheduleReminderDetails['start_action_condition']) {
          $startActionCondition = $scheduleReminderDetails['start_action_condition'];
          $explodeStartActionCondition = explode(',', $startActionCondition);
          $buildRuleArray = array();
          foreach($explodeStartActionCondition as $key => $val) {
            $buildRuleArray[] = strtoupper(substr($val, 0, 2));
          }
          $r->wkst('MO')->byday($buildRuleArray);
        }
      }

      //month 
      if ($scheduleReminderDetails['repetition_frequency_unit'] == 'month') {
        if ($scheduleReminderDetails['entity_status']) {
          $startActionDate = explode(" ", $scheduleReminderDetails['entity_status']);
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
        else if ($scheduleReminderDetails['limit_to']) {
          $r->bymonthday(array($scheduleReminderDetails['limit_to']));
        }
      }

      //Ends
      if ($scheduleReminderDetails['start_action_offset']) {
        if ($scheduleReminderDetails['start_action_offset'] > 30) {
          $r->errors[] = 'Occurrences should be less than or equal to 30';
        }
        $r->count($scheduleReminderDetails['start_action_offset']);
      }

      if (CRM_Utils_Array::value('absolute_date', $scheduleReminderDetails)) {
        $absoluteDate = CRM_Utils_Date::setDateDefaults($scheduleReminderDetails['absolute_date']);
        $endDate = new DateTime($absoluteDate[0].' '.$absoluteDate[1]);
        $r->until($endDate);
      }

      if (!$scheduleReminderDetails['start_action_offset'] && !$scheduleReminderDetails['absolute_date']) {
        $r->errors[] = 'Ends: is a required field';
      }
    }
    else {
      $r->errors[] = 'Repeats: is a required field';
    }
    return $r;
  }
  
  /**
   * This function gets time difference between the two datetime object
   * 
   * @param DateTime $startDate Start Date
   * @param DateTime $endDate End Date
   * 
   * @access public
   * @static
   * 
   * @return object DateTime object which contain time difference
   */
  static public function getInterval($startDate, $endDate) { 
    if ($startDate && $endDate) {
      $startDate = new DateTime($startDate);
      $endDate   = new DateTime($endDate);
      return $startDate->diff($endDate);
    }
  }

  /**
   * This function deletes all the other entities that are related to it
   * 
   * @param int $entityId Entity id
   * @param string $entityTable Name of the entity table
   * 
   * @access public
   * @static
   * 
   * @return boolean|object Returns either boolean value or CRM_Core_DAO_RecurringEntity object
   */
  static public function delEntityRelations($entityId, $entityTable) {
    if (!$entityId && !$entityTable) {
      return FALSE;
    }
    $parentID = self::getParentFor($entityId, $entityTable);
    if ($parentID) {
      $dao = new CRM_Core_DAO_RecurringEntity();
      $dao->parent_id = $parentID;
      return $dao->delete();
    }
  }
}
