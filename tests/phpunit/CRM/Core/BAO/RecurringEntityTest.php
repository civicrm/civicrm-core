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
    //create an activity 
    $daoActivity = new CRM_Activity_DAO_Activity();
    $daoActivity->activity_type_id = 1;
    $daoActivity->subject = "Initial Activity";
    $daoActivity->activity_date_time = date('YmdHis');
    $daoActivity->save();
 
    $this->assertDBNotNull('CRM_Activity_DAO_Activity', $daoActivity->id, 'id',
      'id', 'Check DB if activity was created'
    );

    $recursion = new CRM_Core_BAO_RecurringEntity();
    $recursion->entity_id    = $daoActivity->id;
    $recursion->entity_table = 'civicrm_activity';
    $recursion->dateColumns  = array('activity_date_time');
    $recursion->scheduleDBParams = array(
      'entity_value'      => $daoActivity->id,
      'entity_status'     => $daoActivity->activity_date_time,
      'start_action_date' => 'fourth saturday',
      'repetition_frequency_unit' => 'month',
      'repetition_frequency_interval' => 3,
      'start_action_offset' => 5,
      'used_for' => 'activity'
    );
    $generatedEntities = $recursion->generate(); 
    foreach ($generatedEntities as $entityID) {
      $this->assertDBNotNull('CRM_Activity_DAO_Activity', $entityID, 'id',
        'id', 'Check DB if repeating activities were created'
      );
    }

    // try changing something
    $recursion->mode(3); // sets ->mode var & saves in DB

    // lets change subject of initial activity that we created in begining
    $daoActivity->find(TRUE);
    $daoActivity->subject = 'Changed Activity';
    $daoActivity->save();

    // check if other activities were affected
    foreach ($generatedEntities as $entityID) {
      $this->assertDBCompareValue('CRM_Activity_DAO_Activity', $entityID, 'subject', 'id', 'Changed Activity', 'Check if subject was updated');
    }
  }
  
  /**
   * Testing Event Generation through Entity Recursion
   */
  function testEventGeneration() {
    //Event set initial params
    $daoEvent = new CRM_Event_DAO_Event();
    $daoEvent->title = 'Test event for Recurring Entity';
    $daoEvent->event_type_id = 3;
    $daoEvent->is_public = 1;
    $daoEvent->start_date = date('YmdHis', strtotime('2014-09-24 10:30:00'));
    $daoEvent->end_date =   date('YmdHis', strtotime('2014-09-26 10:30:00'));
    $daoEvent->created_date = date('YmdHis');
    $daoEvent->is_active = 1;
    $daoEvent->save();

    $recursion = new CRM_Core_BAO_RecurringEntity();
    $recursion->entity_id    = $daoEvent->id;
    $recursion->entity_table = 'civicrm_event';
    $recursion->dateColumns  = array('start_date');
    $recursion->scheduleDBParams = array (
      'entity_value'                  => $daoEvent->id,
      'entity_status'                 => $daoEvent->start_date,
      'start_action_condition'        => 'wednesday',
      'repetition_frequency_unit'     => 'week',
      'repetition_frequency_interval' => 1,
      'start_action_offset'           => 4,
      'used_for'                      => 'event'
    );

    //$interval = $recursion->getInterval($daoEvent->start_date, $daoEvent->end_date);
    //$recursion->intervalDateColumns  = array('end_date' => $interval);

    //$recursion->excludeDates = array(date('Ymd', strtotime('2014-10-02')), '20141008');// = array('date1', date2, date2)
    //$recursion->excludeDateRangeColumns = array('start_date', 'end_date');

    $generatedEntities = $recursion->generate(); 
    CRM_Core_Error::debug_var('$generatedEntities', $generatedEntities);

    // try changing something
    $recursion->mode(3); // sets ->mode var & saves in DB

    $daoEvent->find(TRUE);
    $daoEvent->title = 'Event Changed';
    $daoEvent->save();

    // check if other events were affected
    foreach ($generatedEntities as $entityID) {
      $this->assertDBCompareValue('CRM_Event_DAO_Event', $entityID, 'title', 'id', 'Event Changed', 'Check if title was updated');
    }
  }
  
}

  
