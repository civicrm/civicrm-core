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
    $form->add('select', 'repetition_frequency_interval', ts('Repeats every:'), $numericOptions, TRUE, array('style' => 'width:49px;'));
    foreach($dayOfTheWeek as $key => $val){
        $startActionCondition[] = $form->createElement('checkbox', $key, NULL, substr($val."&nbsp;", 0, 3));
    }
    $form->addGroup($startActionCondition, 'start_action_condition', ts('Repeats on'));
    $roptionTypes = array('1' => ts('day of the month'),
        '2' => ts('day of the week'),
      );
    $form->addRadio('repeats_by', ts("Repeats By:"), $roptionTypes, array(), NULL);
    $getMonths = CRM_Core_SelectValues::getNumericOptions(1, 31);
    $form->add('select', 'limit_to', '', $getMonths, FALSE, array('style' => 'width:49px;'));
    $dayOfTheWeekNo = array('first'  => 'First',
                            'second'=> 'Second',
                            'third' => 'Third',
                            'fourth'=> 'Fourth',
                            'last'  => 'Last'
                         );
    $form->add('select', 'start_action_date_1', ts(''), $dayOfTheWeekNo);
    $form->add('select', 'start_action_date_2', ts(''), $dayOfTheWeek);
    $eoptionTypes = array('1' => ts('After'),
        '2' => ts('On'),
      );
    $form->addRadio('ends', ts("Ends:"), $eoptionTypes, array(), NULL, TRUE);
    $form->add('text', 'start_action_offset', ts(''), array('maxlength' => 2));
    $form->addFormRule(array('CRM_Core_Form_RecurringEntity', 'formRule'));
    $form->addDate('repeat_absolute_date', ts('On'), FALSE, array('formatType' => 'mailing'));
    $form->addDate('exclude_date', ts('Exclude Date(s)'), FALSE);
    $select = $form->add('select', 'exclude_date_list', ts(''), $form->_excludeDateInfo, FALSE, array('style' => 'width:200px;', 'size' => 4));
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
//    CRM_Core_Error::debug('$values', $values);
    
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
        if($values['start_action_date_1'] != "" ) {
          $dayOfTheWeekNo = array(first, second, third, fourth, last);
          if(!in_array($values['start_action_date_1'], $dayOfTheWeekNo)){
             $errors['start_action_date_1'] = ts('Invalid option');
          }
        }else{
          $errors['start_action_date_1'] = ts('Invalid option');
        }
        if($values['start_action_date_2'] != "" ) {
          if(!in_array($values['start_action_date_2'], $dayOfTheWeek)){
             $errors['start_action_date_2'] = ts('Invalid day name');
          }
        }else{
          $errors['start_action_date_2'] = ts('Invalid day name');
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
    
    if(CRM_Utils_Array::value('id', $params)){
      CRM_Core_BAO_ActionSchedule::del($params['id']);
      unset($params['id']);
    }
    //Save post params to the schedule reminder table
    $dbParams = CRM_Core_BAO_RecurringEntity::mapFormValuesToDB($params);
    $actionScheduleObj = CRM_Core_BAO_ActionSchedule::add($dbParams);
    
    //Build Recursion Object
    if($actionScheduleObj->id){
      $recursionObject = CRM_Core_BAO_RecurringEntity::getRecursionFromReminder($actionScheduleObj->id);
    }
    
    
    //TO DO - Exclude date functionality
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
          CRM_Core_BAO_OptionValue::add($optionGroupValue);
        }
      }
    }
    
    //Give call to create recursions
    $recurResult = self::generateRecursions($recursionObject, $params);
    if(!empty($recurResult)){
      self::addEntityThroughRecursion($recurResult, $params['parent_event_id']);
    }
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

  static public function generateRecursions($recursionObj, $params=array()){ 
    $newParams = $recursionResult = array();
    if($recursionObj && !empty($params)){ 
      //Proceed only if these keys are found in array
      if(CRM_Utils_Array::value('parent_event_start_date', $params) && CRM_Utils_Array::value('parent_event_id', $params)){
        $count = 1;
        while($result = $recursionObj->next()){
          //$result->format('YmdHis'). '<br />';
          $newParams['start_date'] = CRM_Utils_Date::processDate($result->format('Y-m-d H:i:s'));
          $parentStartDate = new DateTime($params['parent_event_start_date']);
          //If open ended event
          if(CRM_Utils_Array::value('parent_event_end_date', $params)){
            $parentEndDate = new DateTime($params['parent_event_end_date']);
            $interval = $parentStartDate->diff($parentEndDate);
            $end_date = new DateTime($newParams['start_date']);
            $end_date->add($interval);
            $newParams['end_date'] = CRM_Utils_Date::processDate($end_date->format('Y-m-d H:i:s'));
            $recursionResult[$count]['end_date'] = $newParams['end_date'];
          }
          $recursionResult[$count]['start_date'] = $newParams['start_date'];
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
  
  static function getListOfCurrentAndFutureEvents($ids=''){
    if(isset($ids) and !empty($ids)){
      $curDate = date('YmdHis');
      $query = "SELECT group_concat(id) as ids FROM civicrm_event 
                WHERE id IN ({$ids}) 
                AND ( end_date >= {$curDate} OR
                (
                  ( end_date IS NULL OR end_date = '' ) AND start_date >= {$curDate}
                ))";
      $dao = CRM_Core_DAO::executeQuery($query);
      $dao->fetch();
    }
    return $dao;
  }
  
  static function deleleRelationsForEventsInPast($ids=''){
    if(isset($ids) and !empty($ids)){
      $query = "DELETE FROM civicrm_recurring_entity
                WHERE entity_id IN ({$ids})";
      $dao = CRM_Core_DAO::executeQuery($query);
    }
    return; 
  }
   
}
