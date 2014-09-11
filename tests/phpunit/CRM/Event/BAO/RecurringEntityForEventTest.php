<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CRM/Core/BAO/RecurringEntity.php';

/**
 * Class CRM_Event_BAO_RecurringEntityForEventTest
 */
class CRM_Event_BAO_RecurringEntityForEventTest extends CiviUnitTestCase {
  /**
   * @return array
   */
  function get_info() {
    return array(
      'name' => 'Recurring Entity BAOs',
      'description' => 'Test RecurringEntity for Events.',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   *
   * @access protected
   */
  protected function setUp() {
    parent::setUp();
    $daoEvent = new CRM_Event_DAO_Event();
    $daoEvent->title = 'Test event for Recurring Entity';
    $daoEvent->event_type_id = 3;
    $daoEvent->is_public = 1;
    $daoEvent->start_date = date('YmdHis', strtotime('2014-09-24 10:30:00'));
    $daoEvent->end_date = date('YmdHis', strtotime('2014-09-26 10:30:00'));
    $daoEvent->created_date = date('YmdHis');
    $result = $daoEvent->save();
 
    $this->assertDBNotNull('CRM_Event_DAO_Event', $result->id, 'id',
      'id', 'Check DB if event was created'
    );
    $this->_parentID = $result->id;
    $this->start_date = $result->start_date;
    $this->end_date = $result->end_date;
  }
  
   /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  protected function tearDown() {}

  /**
   * quickAdd() method for RecurringEntity 
   */
  function testAdd() {
    if( $this->_parentID ){
      //Add parent to civicrm_recurring_entity table
      $newCreatedParentID = CRM_Core_BAO_RecurringEntity::quickAdd($this->_parentID, $this->_parentID, 'civicrm_event');
      
      //Check if there was a new record created in civicrm_recurring_entity
      $this->assertDBRowExist('CRM_Core_DAO_RecurringEntity', $newCreatedParentID->id, 'Check Db for parent entry');
      
      //Check if parent_id and entity_id column are same for this event id
      $this->assertEquals($newCreatedParentID->parent_id, $newCreatedParentID->entity_id, 'Check if parent id is equal to entity id');
      
      //Lets assume you saved the repeat configuration with these criterias
      /**
       * Event occurs every 2 weeks
       * on monday, wednesday and friday
       * for 4 times
       * For eg - A small course on art and craft
       */
      $dbParams = array (
                    'entity_value'                  => $this->_parentID,
                    'entity_status'                 => $this->start_date,
                    'start_action_condition'        => 'monday,wednesday,friday',
                    'repetition_frequency_unit'     => 'week',
                    'repetition_frequency_interval' => 2,
                    'start_action_offset'           => 4,
                    'used_for'                      => 'event'
      );
      $actionSchedule = new CRM_Core_DAO_ActionSchedule();
      $actionSchedule->copyValues($dbParams);
      $actionSchedule->save();
      
      //Check if repeat configuration got saved in civicrm_action_schedule table
      $this->assertDBNotNull('CRM_Core_DAO_ActionSchedule', $this->_parentID, 'id',
      'entity_value', 'Check there was an entry for repeat configuration with this new event id'
      );
 
      //getRecursionFromReminder builds recursion object for you
      if( $actionSchedule->id ){
        $recursionObject = CRM_Core_BAO_RecurringEntity::getRecursionFromReminder($actionSchedule->id);

        //Check if this is an object of When class
        $this->assertInstanceOf('When', $recursionObject, 'Check for created object');
      }
 
      // Recursion library has returned an array based on the repeat configuration you provided
      $params = array();
      $params['interval'] = 2;
      $recurResult = CRM_Core_BAO_RecurringEntity::generateRecursions($recursionObject, $params);
      
      //Store children events in an array
      $storeNewEvents = array();
      //You can now create event recursively
      foreach( $recurResult as $val ){
        $eventNew = new CRM_Event_DAO_Event();
        $eventNew->title = 'Common title for all events';
        $eventNew->event_type_id = 3;
        $eventNew->is_public = 1;
        $eventNew->start_date = CRM_Utils_Date::processDate($val['start_date']);
        $eventNew->end_date = CRM_Utils_Date::processDate($val['end_date']);
        $eventNew->save();
        
        //Add children to civicrm_recurring_entity table
        CRM_Core_BAO_RecurringEntity::quickAdd($this->_parentID, $eventNew->id, 'civicrm_event');
        $storeNewEvents[] = $eventNew->id;
      }
      foreach($storeNewAcitivities as $val){
        $this->assertDBNotNull('CRM_Event_DAO_Event', $val, 'id',
        'id', 'Check DB if events were created'
        );
      }
    }
  }
  
  /**
   * Check change occurs across related entities for RecurringEntity 
   */
  function testModifyEntity() {
    /**
     * Lets modify an event and see if other related events get cascaded
     */
    $daoRecurringEntity = new CRM_Core_DAO_RecurringEntity();
    $daoRecurringEntity->entity_id = $this->_parentID;
    $daoRecurringEntity->entity_table = 'civicrm_event';
    $daoRecurringEntity->find(TRUE);
    $daoRecurringEntity->mode = 2;
    $daoRecurringEntity->save();
    //Check if mode was changed
    $this->assertDBCompareValue('CRM_Core_DAO_RecurringEntity', $daoRecurringEntity->id, 'mode', 'id', 2, 'Check if mode was updated');

    $daoEvent = new CRM_Event_DAO_Event();
    $daoEvent->id = $this->_parentID;
    $daoEvent->find(TRUE);
    $daoEvent->title = 'Need to change the title for events';
    $daoEvent->save();
    $this->assertDBCompareValue('CRM_Event_DAO_Event', $daoEvent->id, 'title', 'id', 'Need to change the title for events', 'Check if title was updated');

    //Changing any information in parent should change this and following events, given mode 2
    $children = array();
    $children = CRM_Core_BAO_RecurringEntity::getEntitiesForParent($this->_parentID, 'civicrm_event', FALSE);
    foreach( $children as $key => $val ){
      //Check if all the children have their subject updated as that of a parent
      $this->assertDBCompareValue('CRM_Event_DAO_Event', $val['id'], 'title', 'id', 'Need to change the title for events', 'Check if title was updated forr all the children');
    } 
  }
  
}

  
