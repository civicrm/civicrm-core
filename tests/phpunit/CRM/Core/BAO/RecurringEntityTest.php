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

  function setUp() {
    parent::setUp();
    $activity = new CRM_Activity_DAO_Activity();
    $activity->activity_type_id = 7;
    $activity->subject = "Testing activity with recursion";
    $activity->activity_date_time = date('YmdHis');
    $result = $activity->save();
 
    $this->assertDBNotNull('CRM_Activity_DAO_Activity', $result->id, 'id',
      'id', 'Check DB if activity was created'
    );
    $this->_parentID = $result->id;
    $this->_activityDateTime = $result->activity_date_time;
  }

  /**
   * quickAdd() method for RecurringEntity 
   */
  function testAdd() {
    if( $this->_parentID ){
      //Add parent to civicrm_recurring_entity table
      $newCreatedParentID = CRM_Core_BAO_RecurringEntity::quickAdd($this->_parentID, $this->_parentID, 'civicrm_activity');
      
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
                    'entity_value'                  => $this->_parentID,
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
      $this->assertDBNotNull('CRM_Core_DAO_ActionSchedule', $this->_parentID, 'id',
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
        CRM_Core_BAO_RecurringEntity::quickAdd($this->_parentID, $activityNew->id, 'civicrm_activity');
        $storeNewAcitivities[] = $activityNew->id;
      }
      foreach($storeNewAcitivities as $val){
        $this->assertDBNotNull('CRM_Activity_DAO_Activity', $val, 'id',
        'id', 'Check DB if activities were created'
        );
      }
    }
  }
  
  /**
   * Check change occurs across related entities for RecurringEntity 
   */
  function testModifyEntity() {
    /**
     * Lets modify an activity and see if other related activities get cascaded
     */
    $daoRecurringEntity = new CRM_Core_DAO_RecurringEntity();
    $daoRecurringEntity->entity_id = $this->_parentID;
    $daoRecurringEntity->entity_table = 'civicrm_activity';
    $daoRecurringEntity->find(TRUE);
    $daoRecurringEntity->mode = 2;
    $daoRecurringEntity->save();
    //Check if mode was changed
    $this->assertDBCompareValue('CRM_Core_DAO_RecurringEntity', $daoRecurringEntity->id, 'mode', 'id', 2, 'Check if mode was updated');

    $daoActivity = new CRM_Activity_DAO_Activity();
    $daoActivity->id = $this->_parentID;
    $daoActivity->find(TRUE);
    $daoActivity->subject = 'Need to change the subject for activities';
    $daoActivity->save();
    $this->assertDBCompareValue('CRM_Activity_DAO_Activity', $daoActivity->id, 'subject', 'id', 'Need to change the subject for activities', 'Check if subject was updated');

    //Changing any information in parent should change this and following activities given mode 2
    $children = array();
    $children = CRM_Core_BAO_RecurringEntity::getEntitiesForParent($this->_parentID, 'civicrm_activity', FALSE);
    foreach( $children as $key => $val ){
      //Check if all the children have their subject updated as that of a parent
      $this->assertDBCompareValue('CRM_Activity_DAO_Activity', $val['id'], 'subject', 'id', 'Need to change the subject for activities', 'Check if subject was updated forr all the children');
    } 
  }
  
}

  
