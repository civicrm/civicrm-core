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
    //Activity set initial params
    $daoActivity = new CRM_Activity_DAO_Activity();
    $daoActivity->activity_type_id = 1;
    $daoActivity->subject = "Initial Activity";
    $daoActivity->activity_date_time = date('YmdHis');
    $daoActivity->save();

    $recursion = new CRM_Core_BAO_RecurringEntity();
    $recursion->entity_id    = $daoActivity->id;
    $recursion->entity_table = 'civicrm_activity';
    $recursion->dateColumns  = array('activity_date_time');
    $recursion->schedule     = array(
      'entity_value'      => $daoActivity->id,
      'start_action_date'     => $daoActivity->activity_date_time,
      'entity_status' => 'fourth saturday',
      'repetition_frequency_unit' => 'month',
      'repetition_frequency_interval' => 3,
      'start_action_offset' => 5,
      'used_for'            => 'activity'
    );

    $generatedEntities = $recursion->generate(); 
    foreach ($generatedEntities['civicrm_activity'] as $entityID) {
      $this->assertDBNotNull('CRM_Activity_DAO_Activity', $entityID, 'id',
        'id', 'Check DB if repeating activities were created'
      );
    }

    // set mode to ALL, i.e any change to changing activity affects all related recurring activities
    $recursion->mode(3);

    // lets change subject of initial activity that we created in begining
    $daoActivity->find(TRUE);
    $daoActivity->subject = 'Changed Activity';
    $daoActivity->save();

    // check if other activities were affected
    foreach ($generatedEntities['civicrm_activity'] as $entityID) {
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
    $daoEvent->end_date   = date('YmdHis', strtotime('2014-09-26 10:30:00'));
    $daoEvent->created_date = date('YmdHis');
    $daoEvent->is_active = 1;
    $daoEvent->save();
    $this->assertDBNotNull('CRM_Event_DAO_Event', $daoEvent->id, 'id', 'id', 'Check DB if event was created');
    
    //Create profile for event
    $daoUF = new CRM_Core_DAO_UFJoin();
    $daoUF->is_active = 1;
    $daoUF->entity_table = 'civicrm_event';
    $daoUF->entity_id = $daoEvent->id;
    $daoUF->uf_group_id = 12;
    $daoUF->module = 'Test';
    $daoUF->save();
    $this->assertDBNotNull('CRM_Core_DAO_UFJoin', $daoUF->id, 'id', 'id', 'Check DB if profile was created');
    
    //Create tell a friend for event
    $daoTellAFriend = new CRM_Friend_DAO_Friend();
    $daoTellAFriend->entity_table = 'civicrm_event';
    $daoTellAFriend->entity_id = $daoEvent->id;
    $daoTellAFriend->title = 'Testing tell a friend';
    $daoTellAFriend->is_active = 1;
    $daoTellAFriend->save();
    $this->assertDBNotNull('CRM_Friend_DAO_Friend', $daoTellAFriend->id, 'id', 'id', 'Check DB if tell a freind was created');

    $recursion = new CRM_Core_BAO_RecurringEntity();
    $recursion->entity_id    = $daoEvent->id;
    $recursion->entity_table = 'civicrm_event';
    $recursion->dateColumns  = array('start_date');
    $recursion->schedule     = array (
      'entity_value'                  => $daoEvent->id,
      'start_action_date'             => $daoEvent->start_date,
      'start_action_condition'        => 'wednesday',
      'repetition_frequency_unit'     => 'week',
      'repetition_frequency_interval' => 1,
      'start_action_offset'           => 4,
      'used_for'                      => 'event'
    );

    $recursion->linkedEntities = array(
      array(
        'table'         => 'civicrm_price_set_entity',
        'findCriteria'  => array(
          'entity_id'    => $recursion->entity_id, 
          'entity_table' => 'civicrm_event'
        ),
        'linkedColumns' => array('entity_id'),
        'isRecurringEntityRecord' => FALSE,
      ),
      array(
        'table'         => 'civicrm_uf_join',
        'findCriteria'  => array(
          'entity_id'    => $recursion->entity_id, 
          'entity_table' => 'civicrm_event'
        ),
        'linkedColumns' => array('entity_id'),
        'isRecurringEntityRecord' => FALSE,
      ),
      array(
        'table'         => 'civicrm_tell_friend',
        'findCriteria'  => array(
          'entity_id'    => $recursion->entity_id, 
          'entity_table' => 'civicrm_event'
        ),
        'linkedColumns' => array('entity_id'),
        'isRecurringEntityRecord' => TRUE,
      ),
      array(
        'table'         => 'civicrm_pcp_block',
        'findCriteria'  => array(
          'entity_id'    => $recursion->entity_id, 
          'entity_table' => 'civicrm_event'
        ),
        'linkedColumns' => array('entity_id'),
        'isRecurringEntityRecord' => TRUE,
      ),
    );

    $generatedEntities = $recursion->generate(); 
    $this->assertArrayHasKey('civicrm_event', $generatedEntities, 'Check if generatedEntities has civicrm_event as required key');
    
    if(CRM_Utils_Array::value('civicrm_event', $generatedEntities)){
      $expCountEvent = count($generatedEntities['civicrm_event']);
      $actCountEvent = 0;
      foreach($generatedEntities['civicrm_event'] as $key => $val){
        $this->assertDBNotNull('CRM_Event_DAO_Event', $val, 'id', 'id', 'Check if events were created in loop');
        $actCountEvent++;
      }
      $this->assertCount($expCountEvent, $actCountEvent, 'Check if the number of events created are right');
    }
    
    if(CRM_Utils_Array::value('civicrm_uf_join', $generatedEntities) && CRM_Utils_Array::value('civicrm_event', $generatedEntities)){
      $expCountUFJoin = count($generatedEntities['civicrm_uf_join']);
      $actCountUFJoin = 0;
      foreach($generatedEntities['civicrm_uf_join'] as $key => $val){
        $this->assertDBNotNull('CRM_Core_DAO_UFJoin', $val, 'id', 'id', 'Check if profile were created in loop');
        $this->assertDBCompareValue('CRM_Core_DAO_UFJoin', $val, 'entity_id', 'id', $generatedEntities['civicrm_event'][$key], 'Check DB if correct FK was maintained with event for UF Join');
        $actCountUFJoin++;
      }
      $this->assertCount($expCountUFJoin, $actCountUFJoin, 'Check if the number of profiles created are right');
    }
    
    if(CRM_Utils_Array::value('civicrm_tell_friend', $generatedEntities) && CRM_Utils_Array::value('civicrm_event', $generatedEntities)){
      $expCountTellAFriend = count($generatedEntities['civicrm_tell_friend']);
      $actCountTellAFriend = 0;
      foreach($generatedEntities['civicrm_tell_friend'] as $key => $val){
        $this->assertDBNotNull('CRM_Friend_DAO_Friend', $val, 'id', 'id', 'Check if friends were created in loop');
        $this->assertDBCompareValue('CRM_Friend_DAO_Friend', $val, 'entity_id', 'id', $generatedEntities['civicrm_event'][$key], 'Check DB if correct FK was maintained with event for Friend');
        $actCountTellAFriend++;
      }
      $this->assertCount($expCountTellAFriend, $actCountTellAFriend, 'Check if the number of friends created are right');
    }
    

    // set mode to ALL, i.e any change to changing event affects all related recurring activities
    $recursion->mode(3);

    $daoEvent->find(TRUE);
    $daoEvent->title = 'Event Changed';
    $daoEvent->save();

    // check if other events were affected
    foreach ($generatedEntities['civicrm_event'] as $entityID) {
      $this->assertDBCompareValue('CRM_Event_DAO_Event', $entityID, 'title', 'id', 'Event Changed', 'Check if title was updated');
    }
  }
}
