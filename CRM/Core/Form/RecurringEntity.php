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
 * This class generates form components for processing Entity
 *
 */
class CRM_Core_Form_RecurringEntity {
  /**
   *  Current entity id
   */
  protected static $_entityId = NULL;
  
  /**
   * Schedule Reminder ID
   */
  protected static $_scheduleReminderID = NULL;
  
  /**
  * Schedule Reminder data
  */
  protected static $_scheduleReminderDetails = array();
  
  /**
  *  Parent Entity ID
  */
  protected static $_parentEntityId = NULL;
  
  /**
  * Exclude date information 
  */
  public static $_excludeDateInfo = array();
  
  /**
  * Entity Table
  */
  public static $_entityTable;
  
  /**
  * Entity Type
  */
  public static $_entityType;
  
  /**
   * Checks current entityID has parent
   */
  public static $_hasParent = FALSE;
  
  static function preProcess($entityTable) {
    self::$_entityId = (int) CRM_Utils_Request::retrieve('id', 'Positive');
    self::$_entityTable = $entityTable;
    $entityType = array();
    if (self::$_entityId && $entityTable) {
      $checkParentExistsForThisId = CRM_Core_BAO_RecurringEntity::getParentFor(self::$_entityId, $entityTable);    
      $entityType = explode("_", $entityTable);
      self::$_entityType = $entityType[1];
      if (self::$_entityType) {
        self::$_entityType = self::$_entityType;
      }
      if ($checkParentExistsForThisId) {
        self::$_hasParent = TRUE;
        self::$_parentEntityId = $checkParentExistsForThisId;
        self::$_scheduleReminderDetails = CRM_Core_BAO_RecurringEntity::getReminderDetailsByEntityId($checkParentExistsForThisId, self::$_entityType);
      }
      else {
        self::$_parentEntityId = self::$_entityId;
        self::$_scheduleReminderDetails = CRM_Core_BAO_RecurringEntity::getReminderDetailsByEntityId(self::$_entityId, self::$_entityType);
      }
      self::$_scheduleReminderID = self::$_scheduleReminderDetails->id;
    }
    if (self::$_entityType) {
      CRM_Core_OptionValue::getValues(array('name' => self::$_entityType.'_repeat_exclude_dates_'.self::$_parentEntityId), $optionValue);
      $excludeOptionValues = array();
      if (!empty($optionValue)) {
        foreach($optionValue as $key => $val) {
          $excludeOptionValues[$val['value']] = date('m/d/Y', strtotime($val['value']));
        }
        self::$_excludeDateInfo = $excludeOptionValues;
      }
    }
  }
  
   /**
   * This function sets the default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  static function setDefaultValues() {
    $defaults = array();
    if (self::$_scheduleReminderID) {
      $defaults['repetition_frequency_unit'] = self::$_scheduleReminderDetails->repetition_frequency_unit;
      $defaults['repetition_frequency_interval'] = self::$_scheduleReminderDetails->repetition_frequency_interval;
      $defaults['start_action_condition'] = array_flip(explode(",",self::$_scheduleReminderDetails->start_action_condition));
      foreach($defaults['start_action_condition'] as $key => $val) {
        $val = 1;
        $defaults['start_action_condition'][$key] = $val;
      }
      $defaults['start_action_offset'] = self::$_scheduleReminderDetails->start_action_offset;
      if (self::$_scheduleReminderDetails->start_action_offset) {
        $defaults['ends'] = 1;
      }
      list($defaults['repeat_absolute_date']) = CRM_Utils_Date::setDateDefaults(self::$_scheduleReminderDetails->absolute_date);
      if (self::$_scheduleReminderDetails->absolute_date) {
        $defaults['ends'] = 2;
      }
      $defaults['limit_to'] = self::$_scheduleReminderDetails->limit_to;
      if (self::$_scheduleReminderDetails->limit_to) {
        $defaults['repeats_by'] = 1;
      }
      $explodeStartActionCondition = array();
      if (self::$_scheduleReminderDetails->entity_status) {
        $explodeStartActionCondition = explode(" ", self::$_scheduleReminderDetails->entity_status);
        $defaults['entity_status_1'] = $explodeStartActionCondition[0];
        $defaults['entity_status_2'] = $explodeStartActionCondition[1];
      }
      if (self::$_scheduleReminderDetails->entity_status) {
        $defaults['repeats_by'] = 2;
      }
    }
    return $defaults;
  }
  
  static function buildQuickForm(&$form) {
    $form->assign('currentEntityId', self::$_entityId);
    $form->assign('entityTable', self::$_entityTable);
    $form->assign('scheduleReminderId', self::$_scheduleReminderID);
    $form->assign('hasParent', self::$_hasParent);
    
    $form->_freqUnits = array('hour' => 'hour') + CRM_Core_OptionGroup::values('recur_frequency_units');
    foreach ($form->_freqUnits as $val => $label) {
      if ($label == "day") {
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
    foreach($dayOfTheWeek as $key => $val) {
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
    $form->add('text', 'start_action_offset', ts(''), array('size' => 3, 'maxlength' => 2));
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
    if (!CRM_Utils_Array::value('repetition_frequency_unit', $values)) {
      $errors['repetition_frequency_unit'] = ts('This is a required field');
    }
    //Repeats every
    if (!CRM_Utils_Array::value('repetition_frequency_interval', $values)) {
      $errors['repetition_frequency_interval'] = ts('This is a required field');
    }
    //Ends
    if (CRM_Utils_Array::value('ends', $values)) {
      if ($values['ends'] == 1) {
        if ($values['start_action_offset'] == "") {
          $errors['start_action_offset'] = ts('This is a required field');
        }
        else if ($values['start_action_offset'] > 30) {
          $errors['start_action_offset'] = ts('Occurrences should be less than or equal to 30');
        }
      }
      if ($values['ends'] == 2) {
        if ($values['repeat_absolute_date'] != "") {
          $entityStartDate = CRM_Utils_Date::processDate($values['repetition_start_date']);
          $end = CRM_Utils_Date::processDate($values['repeat_absolute_date']);
          if (($end < $entityStartDate) && ($end != 0)) {
            $errors['repeat_absolute_date'] = ts('End date should be after current entity\'s start date');
          }
        }
        else {
          $errors['repeat_absolute_date'] = ts('This is a required field');
        }
      }
    }
    else {
      $errors['ends'] = ts('This is a required field');
    }
    
    //Repeats BY
    if (CRM_Utils_Array::value('repeats_by', $values)) {
      if ($values['repeats_by'] == 1) {
        if ($values['limit_to'] != "") {
          if ($values['limit_to'] < 1 && $values['limit_to'] > 31) {
            $errors['limit_to'] = ts('Invalid day of the month');
          }
        }
        else {
          $errors['limit_to'] = ts('Invalid day of the month');
        }
      }
      if ($values['repeats_by'] == 2) {
        if ($values['entity_status_1'] != "" ) {
          $dayOfTheWeekNo = array(first, second, third, fourth, last);
          if (!in_array($values['entity_status_1'], $dayOfTheWeekNo)) {
             $errors['entity_status_1'] = ts('Invalid option');
          }
        }
        else {
          $errors['entity_status_1'] = ts('Invalid option');
        }
        if ($values['entity_status_2'] != "" ) {
          if (!in_array($values['entity_status_2'], $dayOfTheWeek)) {
             $errors['entity_status_2'] = ts('Invalid day name');
          }
        }
        else {
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
  static function postProcess($params = array(), $type, $linkedEntities = array()) {
    $params['entity_id'] = self::$_entityId;
    if (CRM_Utils_Array::value('entity_table', $params) && CRM_Utils_Array::value('entity_id', $params) && $type) {
      $params['used_for'] = $type;
      $params['parent_entity_id'] = self::$_parentEntityId;
      $params['id'] = self::$_scheduleReminderID;

      //Save post params to the schedule reminder table
      $dbParams = CRM_Core_BAO_RecurringEntity::mapFormValuesToDB($params);

      //Delete repeat configuration and rebuild
      if (CRM_Utils_Array::value('id', $params)) {
        CRM_Core_BAO_ActionSchedule::del($params['id']);
        unset($params['id']);
      }
      $actionScheduleObj = CRM_Core_BAO_ActionSchedule::add($dbParams);

      //exclude dates 
      $excludeDateList = array();
      if (CRM_Utils_Array::value('copyExcludeDates', $params) && CRM_Utils_Array::value('parent_entity_id', $params)) {   
        //Since we get comma separated values lets get them in array
        $exclude_date_list = array();
        $exclude_date_list = explode(",", $params['copyExcludeDates']);

        //Check if there exists any values for this option group
        $optionGroupIdExists = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
            $type.'_repeat_exclude_dates_'.$params['parent_entity_id'],
            'id',
            'name'
          );
        if ($optionGroupIdExists) {
          CRM_Core_BAO_OptionGroup::del($optionGroupIdExists);
        }
        $optionGroupParams = 
            array(
              'name'        => $type.'_repeat_exclude_dates_'.$params['parent_entity_id'],
              'title'       => $type.' recursion',
              'is_reserved' => 0,
              'is_active'   => 1
            );
        $opGroup = CRM_Core_BAO_OptionGroup::add($optionGroupParams);
        if ($opGroup->id) {
          $oldWeight= 0;
          $fieldValues = array('option_group_id' => $opGroup->id);
          foreach($exclude_date_list as $val) {
            $optionGroupValue = 
                array(
                  'option_group_id' =>  $opGroup->id,
                  'label'           =>  CRM_Utils_Date::processDate($val),
                  'value'           =>  CRM_Utils_Date::processDate($val),
                  'name'            =>  $opGroup->name,
                  'description'     =>  'Used for recurring '.$type,
                  'weight'          =>  CRM_Utils_Weight::updateOtherWeights('CRM_Core_DAO_OptionValue', $oldWeight, CRM_Utils_Array::value('weight', $params), $fieldValues),
                  'is_active'       =>  1
                );
            $excludeDateList[] = $optionGroupValue['value'];
            CRM_Core_BAO_OptionValue::add($optionGroupValue);
          }
        }
      }

      //Delete relations if any from recurring entity tables before inserting new relations for this entity id
      if ($params['entity_id']) {
        //If entity has any pre delete function, consider that first
        if (CRM_Utils_Array::value('pre_delete_func', CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']]) &&
            CRM_Utils_Array::value('helper_class', CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']])) {
            call_user_func(array(
              CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']]['helper_class'], 
              call_user_func_array(CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']]['pre_delete_func'], array($params['entity_id'])))
            );
        }
        //Ready to execute delete on entities if it has delete function set
        if (CRM_Utils_Array::value('delete_func', CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']]) &&
            CRM_Utils_Array::value('helper_class', CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']])) {
            //Check if pre delete function has some ids to be deleted
            if (!empty(CRM_Core_BAO_RecurringEntity::$_entitiesToBeDeleted)) {
              foreach (CRM_Core_BAO_RecurringEntity::$_entitiesToBeDeleted as $value) {
                $result = civicrm_api3(ucfirst(strtolower($type)), CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']]['delete_func'], array(
                          'sequential' => 1,
                          'id' => $value,
                          ));
                if ($result['error']) {
                  CRM_Core_Error::statusBounce('Error creating recurring list');
                }
              }
            }
            else {
              $getRelatedEntities = CRM_Core_BAO_RecurringEntity::getEntitiesFor($params['entity_id'], $params['entity_table'], FALSE);
              foreach ($getRelatedEntities as $key => $value) {
                $result = civicrm_api3(ucfirst(strtolower($type)), CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']]['delete_func'], array(
                          'sequential' => 1,
                          'id' => $value['id'],
                          ));
                if ($result['error']) {
                  CRM_Core_Error::statusBounce('Error creating recurring list');
                }
              }
            }
        }
        CRM_Core_BAO_RecurringEntity::delEntityRelations($params['entity_id'], $params['entity_table']);
      }

      $recursion = new CRM_Core_BAO_RecurringEntity();
      $recursion->dateColumns  = $params['dateColumns'];
      $recursion->scheduleId   = $actionScheduleObj->id;

      if (!empty($excludeDateList)) {
        $recursion->excludeDates = $excludeDateList;
        $recursion->excludeDateRangeColumns = $params['excludeDateRangeColumns'];
      }
      $recursion->intervalDateColumns = $params['intervalDateColumns'];
      $recursion->entity_id = $params['entity_id'];
      $recursion->entity_table = $params['entity_table'];
      if (!empty($linkedEntities)) {
        $recursion->linkedEntities = $linkedEntities;
      }

      $recurResult = $recursion->generate(); 

      $status = ts('Repeat Configuration has been saved');
      CRM_Core_Session::setStatus($status, ts('Saved'), 'success');
    }
  }
  //end of function

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Repeat Entity');
  }
     
}
