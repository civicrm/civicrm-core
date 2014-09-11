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
 * Class CRM_Core_BAO_RecurringEntityTest
 */
class CRM_Core_BAO_RecurringEntityTest extends CiviUnitTestCase {
  /**
   * @return array
   */
  function get_info() {
    return array(
      'name' => 'Recurring Entity BAOs',
      'description' => 'Test all CRM_Event_BAO_RecurringEntity methods.',
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
    //Activity set initial params
    $daoActivity = new CRM_Activity_DAO_Activity();
    $daoActivity->activity_type_id = 7;
    $daoActivity->subject = "Testing activity with recursion";
    $daoActivity->activity_date_time = date('YmdHis');
    $actResult = $daoActivity->save();
 
    $this->assertDBNotNull('CRM_Activity_DAO_Activity', $actResult->id, 'id',
      'id', 'Check DB if activity was created'
    );
    $this->_actParentID = $actResult->id;
    $this->_activityDateTime = $actResult->activity_date_time;
    
    //Event set initial params
    $daoEvent = new CRM_Event_DAO_Event();
    $daoEvent->title = 'Test event for Recurring Entity';
    $daoEvent->event_type_id = 3;
    $daoEvent->is_public = 1;
    $daoEvent->start_date = date('YmdHis', strtotime('2014-09-24 10:30:00'));
    $daoEvent->end_date = date('YmdHis', strtotime('2014-09-26 10:30:00'));
    $daoEvent->created_date = date('YmdHis');
    $eventResult = $daoEvent->save();
 
    $this->assertDBNotNull('CRM_Event_DAO_Event', $eventResult->id, 'id',
      'id', 'Check DB if event was created'
    );
    $this->_eventParentID = $eventResult->id;
    $this->start_date = $eventResult->start_date;
    $this->end_date = $eventResult->end_date;
  }
  
  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  protected function tearDown() {}

  /**
   * Testing Activity Generation through Entity Recursion
   */
  function testActivityGeneration() {
    if( $this->_actParentID ){
      //Add parent to civicrm_recurring_entity table
      $newCreatedParentID = CRM_Core_BAO_RecurringEntity::quickAdd($this->_actParentID, $this->_actParentID, 'civicrm_activity');
      
      //Check if there was a new record created in civicrm_recurring_entity
      $this->assertDBRowExist('CRM_Core_DAO_RecurringEntity', $newCreatedParentID->id, 'Check Db for parent entry');
      
      //Check if parent_id and entity_id column are same for this activity id
      $this->assertEquals($newCreatedParentID->parent_id, $newCreatedParentID->entity_id, 'Check if parent id is equal to entity id');
      
      //Lets assume you saved the repeat configuration with these criterias
      /**
       * Activity occurs every 3 months 
       * on fouth saturday 
       * for 5 times
       * For eg - Employee performance review recruited on a temporary basis
       */
      $dbParams = array (
                    'entity_value'                  => $this->_actParentID,
                    'entity_status'                 => $this->_activityDateTime,
                    'start_action_date'             => 'fourth saturday',
                    'repetition_frequency_unit'     => 'month',
                    'repetition_frequency_interval' => 3,
                    'start_action_offset'           => 5,
                    'used_for'                      => 'activity'
      );
      $actionSchedule = new CRM_Core_DAO_ActionSchedule();
      $actionSchedule->copyValues($dbParams);
      $actionSchedule->save();
      
      //Check if repeat configuration got saved in civicrm_action_schedule table
      $this->assertDBNotNull('CRM_Core_DAO_ActionSchedule', $this->_actParentID, 'id',
      'entity_value', 'Check there was an entry for repeat configuration with this new activity id'
      );
 
      //getRecursionFromReminder builds recursion object for you
      if( $actionSchedule->id ){
        $recursionObject = CRM_Core_BAO_RecurringEntity::getRecursionFromReminder($actionSchedule->id);

        //Check if this is an object of When class
        $this->assertInstanceOf('When', $recursionObject, 'Check for created object');
      }
 
      // Recursion library has returned an array based on the repeat configuration you provided
      $recurResult = CRM_Core_BAO_RecurringEntity::generateRecursions($recursionObject);
      
      //Store children activities in an array
      $storeNewAcitivities = array();
      //You can now create activity recursively
      foreach( $recurResult as $val ){
        $activityNew = new CRM_Activity_DAO_Activity();
        $activityNew->activity_type_id = 7;
        $activityNew->subject = 'Common subject for all the activities';
        $activityNew->activity_date_time = CRM_Utils_Date::processDate($val['start_date']);
        $activityNew->save();
        
        //Add children to civicrm_recurring_entity table
        CRM_Core_BAO_RecurringEntity::quickAdd($this->_actParentID, $activityNew->id, 'civicrm_activity');
        $storeNewAcitivities[] = $activityNew->id;
      }
      foreach($storeNewAcitivities as $val){
        $this->assertDBNotNull('CRM_Activity_DAO_Activity', $val, 'id',
        'id', 'Check DB if activities were created'
        );
      }
    }
    
    /**
     * Lets modify an activity and see if other related activities get cascaded
     */
    $daoRecurringEntity = new CRM_Core_DAO_RecurringEntity();
    $daoRecurringEntity->entity_id = $this->_actParentID;
    $daoRecurringEntity->entity_table = 'civicrm_activity';
    $daoRecurringEntity->find(TRUE);
    $daoRecurringEntity->mode = 2;
    $daoRecurringEntity->save();
    //Check if mode was changed
    $this->assertDBCompareValue('CRM_Core_DAO_RecurringEntity', $daoRecurringEntity->id, 'mode', 'id', 2, 'Check if mode was updated');

    $daoActivity = new CRM_Activity_DAO_Activity();
    $daoActivity->id = $this->_actParentID;
    $daoActivity->find(TRUE);
    $daoActivity->subject = 'Need to change the subject for activities';
    $daoActivity->save();
    $this->assertDBCompareValue('CRM_Activity_DAO_Activity', $daoActivity->id, 'subject', 'id', 'Need to change the subject for activities', 'Check if subject was updated');

    //Changing any information in parent should change this and following activities given mode 2
    $children = array();
    $children = CRM_Core_BAO_RecurringEntity::getEntitiesForParent($this->_actParentID, 'civicrm_activity', FALSE);
    foreach( $children as $key => $val ){
      //Check if all the children have their subject updated as that of a parent
      $this->assertDBCompareValue('CRM_Activity_DAO_Activity', $val['id'], 'subject', 'id', 'Need to change the subject for activities', 'Check if subject was updated forr all the children');
    } 
  }
  
  /**
   * Testing Event Generation through Entity Recursion
   */
  function testEventGeneration(){
    if( $this->_eventParentID ){
      //Add parent to civicrm_recurring_entity table
      $newCreatedParentID = CRM_Core_BAO_RecurringEntity::quickAdd($this->_eventParentID, $this->_eventParentID, 'civicrm_event');
      
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
                    'entity_value'                  => $this->_eventParentID,
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
      $this->assertDBNotNull('CRM_Core_DAO_ActionSchedule', $this->_eventParentID, 'id',
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
        CRM_Core_BAO_RecurringEntity::quickAdd($this->_eventParentID, $eventNew->id, 'civicrm_event');
        $storeNewEvents[] = $eventNew->id;
      }
      foreach($storeNewAcitivities as $val){
        $this->assertDBNotNull('CRM_Event_DAO_Event', $val, 'id',
        'id', 'Check DB if events were created'
        );
      }
    }
    
    /**
     * Lets modify an event and see if other related events get cascaded
     */
    $daoRecurringEntity = new CRM_Core_DAO_RecurringEntity();
    $daoRecurringEntity->entity_id = $this->_eventParentID;
    $daoRecurringEntity->entity_table = 'civicrm_event';
    $daoRecurringEntity->find(TRUE);
    $daoRecurringEntity->mode = 2;
    $daoRecurringEntity->save();
    //Check if mode was _eventParentID
    $this->assertDBCompareValue('CRM_Core_DAO_RecurringEntity', $daoRecurringEntity->id, 'mode', 'id', 2, 'Check if mode was updated');

    $daoEvent = new CRM_Event_DAO_Event();
    $daoEvent->id = $this->_eventParentID;
    $daoEvent->find(TRUE);
    $daoEvent->title = 'Need to change the title for events';
    $daoEvent->save();
    $this->assertDBCompareValue('CRM_Event_DAO_Event', $daoEvent->id, 'title', 'id', 'Need to change the title for events', 'Check if title was updated');

    //Changing any information in parent should change this and following events, given mode 2
    $children = array();
    $children = CRM_Core_BAO_RecurringEntity::getEntitiesForParent($this->_eventParentID, 'civicrm_event', FALSE);
    foreach( $children as $key => $val ){
      //Check if all the children have their subject updated as that of a parent
      $this->assertDBCompareValue('CRM_Event_DAO_Event', $val['id'], 'title', 'id', 'Need to change the title for events', 'Check if title was updated for all the children');
    } 
  }
  
}

  
