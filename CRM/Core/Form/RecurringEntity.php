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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
/**
 * This class generates form components for processing Event
 *
 */
class CRM_Core_Form_RecurringEntity {
  /**
   *  Current entity id
   */
  protected static $_entityId = NULL;
  
  static function buildQuickForm(&$form) {
    //$attributes_schedule = CRM_Core_DAO::getAttribute('CRM_Core_DAO_ActionMapping');
    self::$_entityId = CRM_Utils_Array::value('id', $_GET);
    $form->assign('currentEntityId', self::$_entityId);
    
    $form->_freqUnits = array('hour' => 'hour') + CRM_Core_OptionGroup::values('recur_frequency_units');
    foreach ($form->_freqUnits as $val => $label) {
      if($label == "day"){
          $label = "dai";
      }
      $freqUnitsDisplay[$val] = ts('%1ly', array(1 => $label));
    }
   // echo "<pre>";print_r($freqUnitsDisplay);
    $dayOfTheWeek = array('monday'   => 'Monday',
                          'tuesday'   => 'Tuesday',
                          'wednesday' => 'Wednesday',
                          'thursday'  => 'Thursday',
                          'friday'    => 'Friday',
                          'saturday'  => 'Saturday',
                          'sunday'    => 'Sunday'
                         );
    $form->add('select', 'repetition_frequency_unit', ts('Repeats:'), $freqUnitsDisplay, TRUE);
    $numericOptions = CRM_Core_SelectValues::getNumericOptions(1, 30);
    $form->add('select', 'repetition_frequency_interval', ts('Repeats every:'), $numericOptions, TRUE, array('style' => 'width:55px;'));
    $form->addDateTime('repetition_start_date', ts('Repetition Start Date'), FALSE, array('formatType' => 'activityDateTime'));
    foreach($dayOfTheWeek as $key => $val){
        $startActionCondition[] = $form->createElement('checkbox', $key, NULL, substr($val."&nbsp;", 0, 3));
    }
    $form->addGroup($startActionCondition, 'start_action_condition', ts('Repeats on'));
    $roptionTypes = array('1' => ts('day of the month'),
        '2' => ts('day of the week'),
      );
    $form->addRadio('repeats_by', ts("Repeats By:"), $roptionTypes, array(), NULL);
    $getMonths = CRM_Core_SelectValues::getNumericOptions(1, 31);
    $form->add('select', 'limit_to', '', $getMonths, FALSE, array('style' => 'width:55px;'));
    $dayOfTheWeekNo = array('first'  => 'First',
                            'second'=> 'Second',
                            'third' => 'Third',
                            'fourth'=> 'Fourth',
                            'last'  => 'Last'
                         );
    $form->add('select', 'entity_status_1', ts(''), $dayOfTheWeekNo);
    $form->add('select', 'entity_status_2', ts(''), $dayOfTheWeek);
    $eoptionTypes = array('1' => ts('After'),
        '2' => ts('On'),
      );
    $form->addRadio('ends', ts("Ends:"), $eoptionTypes, array(), NULL, TRUE);
    $form->add('text', 'start_action_offset', ts(''), array('maxlength' => 2));
    $form->addFormRule(array('CRM_Core_Form_RecurringEntity', 'formRule'));
    $form->addDate('repeat_absolute_date', ts('On'), FALSE, array('formatType' => 'mailing'));
    $form->addDate('exclude_date', ts('Exclude Date(s)'), FALSE);
    $select = $form->add('select', 'exclude_date_list', ts(''), $form->_excludeDateInfo, FALSE, array('style' => 'width:150px;', 'size' => 4));
    $select->setMultiple(TRUE);
    $form->addElement('button','add_to_exclude_list','>>','onClick="addToExcludeList(document.getElementById(\'exclude_date\').value);"'); 
    $form->addElement('button','remove_from_exclude_list', '<<', 'onClick="removeFromExcludeList(\'exclude_date_list\')"'); 
    $form->addElement('hidden', 'isChangeInRepeatConfiguration', '', array('id' => 'isChangeInRepeatConfiguration'));
    $form->addElement('hidden', 'copyExcludeDates', '', array('id' => 'copyExcludeDates'));
    $form->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel')
        ),
      )
    );
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($values) {
    $errors = array();
    $dayOfTheWeek = array(monday,tuesday,wednesday,thursday,friday,saturday,sunday);
    
    //Repeats
    if(!CRM_Utils_Array::value('repetition_frequency_unit', $values)){
      $errors['repetition_frequency_unit'] = ts('This is a required field');
    }
    //Repeats every
    if(!CRM_Utils_Array::value('repetition_frequency_interval', $values)){
      $errors['repetition_frequency_interval'] = ts('This is a required field');
    }
    //Ends
    if(CRM_Utils_Array::value('ends', $values)){
      if($values['ends'] == 1){
        if ($values['start_action_offset'] == "") {
          $errors['start_action_offset'] = ts('This is a required field');
        }else if($values['start_action_offset'] > 30){
          $errors['start_action_offset'] = ts('Occurrences should be less than or equal to 30');
        }
      }
      if($values['ends'] == 2){
        if ($values['repeat_absolute_date'] != "") {
          $today = date("Y-m-d H:i:s"); 
          $today = CRM_Utils_Date::processDate($today);
          $end = CRM_Utils_Date::processDate($values['repeat_absolute_date']);
          if (($end <= $today) && ($end != 0)) {
            $errors['repeat_absolute_date'] = ts('End date should be after today\'s date');
          }
        }else{
          $errors['repeat_absolute_date'] = ts('This is a required field');
        }
      }
    }else{
      $errors['ends'] = ts('This is a required field');
    }
    
    //Repeats BY
    if(CRM_Utils_Array::value('repeats_by', $values)){
      if($values['repeats_by'] == 1){
        if($values['limit_to'] != ""){
          if($values['limit_to'] < 1 && $values['limit_to'] > 31){
            $errors['limit_to'] = ts('Invalid day of the month');
          }
        }else{
          $errors['limit_to'] = ts('Invalid day of the month');
        }
      }
      if($values['repeats_by'] == 2){
        if($values['entity_status_1'] != "" ) {
          $dayOfTheWeekNo = array(first, second, third, fourth, last);
          if(!in_array($values['entity_status_1'], $dayOfTheWeekNo)){
             $errors['entity_status_1'] = ts('Invalid option');
          }
        }else{
          $errors['entity_status_1'] = ts('Invalid option');
        }
        if($values['entity_status_2'] != "" ) {
          if(!in_array($values['entity_status_2'], $dayOfTheWeek)){
             $errors['entity_status_2'] = ts('Invalid day name');
          }
        }else{
          $errors['entity_status_2'] = ts('Invalid day name');
        }
      }
    }
    return $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  static function postProcess($params=array(), $type) {
    if(!empty($type)){
      $params['used_for'] = $type;
    }
    
    //Save post params to the schedule reminder table
    $dbParams = CRM_Core_BAO_RecurringEntity::mapFormValuesToDB($params);

    //Delete repeat configuration and rebuild
    if(CRM_Utils_Array::value('id', $params)){
      CRM_Core_BAO_ActionSchedule::del($params['id']);
      unset($params['id']);
    }
    $actionScheduleObj = CRM_Core_BAO_ActionSchedule::add($dbParams);
    
    //exclude dates 
    $excludeDateList = array();
    if(CRM_Utils_Array::value('copyExcludeDates', $params) && CRM_Utils_Array::value('parent_event_id', $params)){   
      //Since we get comma separated values lets get them in array
      $exclude_date_list = array();
      $exclude_date_list = explode(",", $params['copyExcludeDates']);

      //Check if there exists any values for this option group
      $optionGroupIdExists = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
          'event_repeat_exclude_dates_'.$params['parent_event_id'],
          'id',
          'name'
        );
      if($optionGroupIdExists){
        CRM_Core_BAO_OptionGroup::del($optionGroupIdExists);
      }
      $optionGroupParams = 
          array(
            'name'        => 'event_repeat_exclude_dates_'.$params['parent_event_id'],
            'title'       => 'Event Recursion',
            'is_reserved' => 0,
            'is_active'   => 1
          );
      $opGroup = CRM_Core_BAO_OptionGroup::add($optionGroupParams);
      if($opGroup->id){
        $oldWeight= 0;
        $fieldValues = array('option_group_id' => $opGroup->id);
        foreach($exclude_date_list as $val){
          $optionGroupValue = 
              array(
                'option_group_id' =>  $opGroup->id,
                'label'           =>  CRM_Utils_Date::processDate($val),
                'value'           =>  CRM_Utils_Date::processDate($val),
                'name'            =>  $opGroup->name,
                'description'     =>  'Used for event recursion',
                'weight'          =>  CRM_Utils_Weight::updateOtherWeights('CRM_Core_DAO_OptionValue', $oldWeight, CRM_Utils_Array::value('weight', $params), $fieldValues),
                'is_active'       =>  1
              );
          $excludeDateList[] = $optionGroupValue['value'];
          CRM_Core_BAO_OptionValue::add($optionGroupValue);
        }
      }
    }

    //Delete relations if any from recurring entity tables before inserting new relations for this entity id
    if($params['event_id']){
      $getRelatedEntities = CRM_Core_BAO_RecurringEntity::getEntitiesFor($params['event_id'], 'civicrm_event', TRUE);
      $participantDetails = CRM_Core_BAO_RecurringEntity::getParticipantCountforEvent($getRelatedEntities);
      //Check if participants exists for events
      foreach ($getRelatedEntities as $key => $value) {
        if(!CRM_Utils_Array::value($value['id'], $participantDetails['countByID']) && $value['id'] != $params['event_id']){
          CRM_Event_BAO_Event::del($value['id']);
        }
      }
      CRM_Core_BAO_RecurringEntity::delEntityRelations($params['event_id'], 'civicrm_event');
    }

    $recursion = new CRM_Core_BAO_RecurringEntity();
    $recursion->dateColumns  = array('start_date');
    $recursion->scheduleId   = $actionScheduleObj->id;

    if (!empty($excludeDateList)) {
      $recursion->excludeDates = $excludeDateList;
      $recursion->excludeDateRangeColumns = array('start_date', 'end_date');
    }

    if ($params['parent_event_end_date']) {
      $interval = $recursion->getInterval($params['parent_event_start_date'], $params['parent_event_end_date']);
      $recursion->intervalDateColumns = array('end_date' => $interval);
    }

    $recursion->entity_id = $params['event_id'];
    $recursion->entity_table = 'civicrm_event';
    $recursion->linkedEntities = array(
      array(
        'table'         => 'civicrm_price_set_entity',
        'findCriteria'  => array(
          'entity_id'    => $recursion->entity_id, 
          'entity_table' => 'civicrm_event'
        ),
        'linkedColumns' => array('entity_id'),
        'isRecurringEntityRecord' => TRUE,
      ),
      array(
        'table'         => 'civicrm_uf_join',
        'findCriteria'  => array(
          'entity_id'    => $recursion->entity_id, 
          'entity_table' => 'civicrm_event'
        ),
        'linkedColumns' => array('entity_id'),
        'isRecurringEntityRecord' => TRUE,
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

    $recurResult = $recursion->generate(); 

    $status = ts('Repeat Configuration has been saved');
    CRM_Core_Session::setStatus($status, ts('Saved'), 'success');
  }
  //end of function

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Repeat Event');
  }
     
}
