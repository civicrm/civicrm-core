<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class generates form components for processing Entity.
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
  protected static $_scheduleReminderDetails = [];

  /**
   *  Parent Entity ID
   */
  protected static $_parentEntityId = NULL;

  /**
   * Exclude date information
   */
  public static $_excludeDateInfo = [];

  /**
   * Entity Table
   */
  public static $_entityTable;

  /**
   * Checks current entityID has parent
   */
  public static $_hasParent = FALSE;

  /**
   * @param $entityTable
   */
  public static function preProcess($entityTable) {
    self::$_entityId = (int) CRM_Utils_Request::retrieve('id', 'Positive');
    self::$_entityTable = $entityTable;

    if (self::$_entityId && $entityTable) {
      $checkParentExistsForThisId = CRM_Core_BAO_RecurringEntity::getParentFor(self::$_entityId, $entityTable);
      if ($checkParentExistsForThisId) {
        self::$_hasParent = TRUE;
        self::$_parentEntityId = $checkParentExistsForThisId;
        self::$_scheduleReminderDetails = CRM_Core_BAO_RecurringEntity::getReminderDetailsByEntityId($checkParentExistsForThisId, $entityTable);
      }
      else {
        self::$_parentEntityId = self::$_entityId;
        self::$_scheduleReminderDetails = CRM_Core_BAO_RecurringEntity::getReminderDetailsByEntityId(self::$_entityId, $entityTable);
      }
      if (property_exists(self::$_scheduleReminderDetails, 'id')) {
        self::$_scheduleReminderID = self::$_scheduleReminderDetails->id;
      }
    }
    CRM_Core_OptionValue::getValues(['name' => $entityTable . '_repeat_exclude_dates_' . self::$_parentEntityId], $optionValue);
    $excludeOptionValues = [];
    if (!empty($optionValue)) {
      foreach ($optionValue as $key => $val) {
        $excludeOptionValues[$val['value']] = substr(CRM_Utils_Date::mysqlToIso($val['value']), 0, 10);
      }
      self::$_excludeDateInfo = $excludeOptionValues;
    }

    // Assign variables
    $entityType = CRM_Core_DAO_AllCoreTables::getBriefName(CRM_Core_DAO_AllCoreTables::getClassForTable($entityTable));
    $tpl = CRM_Core_Smarty::singleton();
    $tpl->assign('recurringEntityType', ts($entityType));
    $tpl->assign('currentEntityId', self::$_entityId);
    $tpl->assign('entityTable', self::$_entityTable);
    $tpl->assign('scheduleReminderId', self::$_scheduleReminderID);
    $tpl->assign('hasParent', self::$_hasParent);
  }

  /**
   * Set default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return array
   */
  public static function setDefaultValues() {
    // Defaults for new entity
    $defaults = [
      'repetition_frequency_unit' => 'week',
    ];

    // Default for existing entity
    if (self::$_scheduleReminderID) {
      $defaults['repetition_frequency_unit'] = self::$_scheduleReminderDetails->repetition_frequency_unit;
      $defaults['repetition_frequency_interval'] = self::$_scheduleReminderDetails->repetition_frequency_interval;
      $defaults['start_action_condition'] = array_flip(explode(",", self::$_scheduleReminderDetails->start_action_condition));
      foreach ($defaults['start_action_condition'] as $key => $val) {
        $val = 1;
        $defaults['start_action_condition'][$key] = $val;
      }
      $defaults['start_action_offset'] = self::$_scheduleReminderDetails->start_action_offset;
      if (self::$_scheduleReminderDetails->start_action_offset) {
        $defaults['ends'] = 1;
      }
      $defaults['repeat_absolute_date'] = self::$_scheduleReminderDetails->absolute_date;
      if (self::$_scheduleReminderDetails->absolute_date) {
        $defaults['ends'] = 2;
      }
      $defaults['limit_to'] = self::$_scheduleReminderDetails->limit_to;
      if (self::$_scheduleReminderDetails->limit_to) {
        $defaults['repeats_by'] = 1;
      }
      if (self::$_scheduleReminderDetails->entity_status) {
        $explodeStartActionCondition = explode(" ", self::$_scheduleReminderDetails->entity_status);
        $defaults['entity_status_1'] = $explodeStartActionCondition[0];
        $defaults['entity_status_2'] = $explodeStartActionCondition[1];
      }
      if (self::$_scheduleReminderDetails->entity_status) {
        $defaults['repeats_by'] = 2;
      }
      if (self::$_excludeDateInfo) {
        $defaults['exclude_date_list'] = implode(',', self::$_excludeDateInfo);
      }
    }
    return $defaults;
  }

  /**
   * Build form.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildQuickForm(&$form) {
    // FIXME: this is using the following as keys rather than the standard numeric keys returned by CRM_Utils_Date
    $dayOfTheWeek = [];
    $dayKeys = [
      'sunday',
      'monday',
      'tuesday',
      'wednesday',
      'thursday',
      'friday',
      'saturday',
    ];
    foreach (CRM_Utils_Date::getAbbrWeekdayNames() as $k => $label) {
      $dayOfTheWeek[$dayKeys[$k]] = $label;
    }
    $form->add('select', 'repetition_frequency_unit', ts('Repeats every'), CRM_Core_SelectValues::getRecurringFrequencyUnits(), FALSE, ['class' => 'required']);
    $numericOptions = CRM_Core_SelectValues::getNumericOptions(1, 30);
    $form->add('select', 'repetition_frequency_interval', NULL, $numericOptions, FALSE, ['class' => 'required']);
    $form->add('datepicker', 'repetition_start_date', ts('Start Date'), [], FALSE, ['time' => TRUE]);
    foreach ($dayOfTheWeek as $key => $val) {
      $startActionCondition[] = $form->createElement('checkbox', $key, NULL, $val);
    }
    $form->addGroup($startActionCondition, 'start_action_condition', ts('Repeats on'));
    $roptionTypes = [
      '1' => ts('day of the month'),
      '2' => ts('day of the week'),
    ];
    $form->addRadio('repeats_by', ts("Repeats on"), $roptionTypes, ['required' => TRUE], NULL);
    $form->add('select', 'limit_to', '', CRM_Core_SelectValues::getNumericOptions(1, 31));
    $dayOfTheWeekNo = [
      'first' => ts('First'),
      'second' => ts('Second'),
      'third' => ts('Third'),
      'fourth' => ts('Fourth'),
      'last' => ts('Last'),
    ];
    $form->add('select', 'entity_status_1', '', $dayOfTheWeekNo);
    $form->add('select', 'entity_status_2', '', $dayOfTheWeek);
    $eoptionTypes = [
      '1' => ts('After'),
      '2' => ts('On'),
    ];
    $form->addRadio('ends', ts("Ends"), $eoptionTypes, ['class' => 'required'], NULL);
    // Offset options gets key=>val pairs like 1=>2 because the BAO wants to know the number of
    // children while it makes more sense to the user to see the total number including the parent.
    $offsetOptions = range(1, 30);
    unset($offsetOptions[0]);
    $form->add('select', 'start_action_offset', NULL, $offsetOptions, FALSE);
    $form->addFormRule(['CRM_Core_Form_RecurringEntity', 'formRule']);
    $form->add('datepicker', 'repeat_absolute_date', ts('On'), [], FALSE, ['time' => FALSE]);
    $form->add('text', 'exclude_date_list', ts('Exclude Dates'), ['class' => 'twenty']);
    $form->addElement('hidden', 'allowRepeatConfigToSubmit', '', ['id' => 'allowRepeatConfigToSubmit']);
    $form->addButtons([
        [
          'type' => 'submit',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
    // For client-side pluralization
    $form->assign('recurringFrequencyOptions', [
      'single' => CRM_Utils_Array::makeNonAssociative(CRM_Core_SelectValues::getRecurringFrequencyUnits()),
      'plural' => CRM_Utils_Array::makeNonAssociative(CRM_Core_SelectValues::getRecurringFrequencyUnits(2)),
    ]);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   *   Posted values of the form.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values) {
    $errors = [];
    //Process this function only when you get this variable
    if ($values['allowRepeatConfigToSubmit'] == 1) {
      $dayOfTheWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
      //Repeats
      if (empty($values['repetition_frequency_unit'])) {
        $errors['repetition_frequency_unit'] = ts('This is a required field');
      }
      //Repeats every
      if (empty($values['repetition_frequency_interval'])) {
        $errors['repetition_frequency_interval'] = ts('This is a required field');
      }
      //Ends
      if (!empty($values['ends'])) {
        if ($values['ends'] == 1) {
          if (empty($values['start_action_offset'])) {
            $errors['start_action_offset'] = ts('This is a required field');
          }
          elseif ($values['start_action_offset'] > 30) {
            $errors['start_action_offset'] = ts('Occurrences should be less than or equal to 30');
          }
        }
        if ($values['ends'] == 2) {
          if (!empty($values['repeat_absolute_date'])) {
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
      if (!empty($values['repeats_by'])) {
        if ($values['repeats_by'] == 1) {
          if (!empty($values['limit_to'])) {
            if ($values['limit_to'] < 1 && $values['limit_to'] > 31) {
              $errors['limit_to'] = ts('Invalid day of the month');
            }
          }
          else {
            $errors['limit_to'] = ts('Invalid day of the month');
          }
        }
        if ($values['repeats_by'] == 2) {
          if (!empty($values['entity_status_1'])) {
            $dayOfTheWeekNo = ['first', 'second', 'third', 'fourth', 'last'];
            if (!in_array($values['entity_status_1'], $dayOfTheWeekNo)) {
              $errors['entity_status_1'] = ts('Invalid option');
            }
          }
          else {
            $errors['entity_status_1'] = ts('Invalid option');
          }
          if (!empty($values['entity_status_2'])) {
            if (!in_array($values['entity_status_2'], $dayOfTheWeek)) {
              $errors['entity_status_2'] = ts('Invalid day name');
            }
          }
          else {
            $errors['entity_status_2'] = ts('Invalid day name');
          }
        }
      }
    }
    return $errors;
  }

  /**
   * Process the form submission.
   *
   * @param array $params
   * @param string $type
   * @param array $linkedEntities
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function postProcess($params = [], $type, $linkedEntities = []) {
    // Check entity_id not present in params take it from class variable
    if (empty($params['entity_id'])) {
      $params['entity_id'] = self::$_entityId;
    }
    //Process this function only when you get this variable
    if ($params['allowRepeatConfigToSubmit'] == 1) {
      if (!empty($params['entity_table']) && !empty($params['entity_id']) && $type) {
        $params['used_for'] = $type;
        if (empty($params['parent_entity_id'])) {
          $params['parent_entity_id'] = self::$_parentEntityId;
        }
        if (!empty($params['schedule_reminder_id'])) {
          $params['id'] = $params['schedule_reminder_id'];
        }
        else {
          $params['id'] = self::$_scheduleReminderID;
        }

        //Save post params to the schedule reminder table
        $recurobj = new CRM_Core_BAO_RecurringEntity();
        $dbParams = $recurobj->mapFormValuesToDB($params);

        //Delete repeat configuration and rebuild
        if (!empty($params['id'])) {
          CRM_Core_BAO_ActionSchedule::del($params['id']);
          unset($params['id']);
        }
        $actionScheduleObj = CRM_Core_BAO_ActionSchedule::add($dbParams);

        //exclude dates
        $excludeDateList = [];
        if (CRM_Utils_Array::value('exclude_date_list', $params) && CRM_Utils_Array::value('parent_entity_id', $params) && $actionScheduleObj->entity_value) {
          //Since we get comma separated values lets get them in array
          $excludeDates = explode(",", $params['exclude_date_list']);

          //Check if there exists any values for this option group
          $optionGroupIdExists = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
            $type . '_repeat_exclude_dates_' . $params['parent_entity_id'],
            'id',
            'name'
          );
          if ($optionGroupIdExists) {
            CRM_Core_BAO_OptionGroup::del($optionGroupIdExists);
          }
          $optionGroupParams = [
            'name' => $type . '_repeat_exclude_dates_' . $actionScheduleObj->entity_value,
            'title' => $type . ' recursion',
            'is_reserved' => 0,
            'is_active' => 1,
          ];
          $opGroup = CRM_Core_BAO_OptionGroup::add($optionGroupParams);
          if ($opGroup->id) {
            $oldWeight = 0;
            $fieldValues = ['option_group_id' => $opGroup->id];
            foreach ($excludeDates as $val) {
              $optionGroupValue = [
                'option_group_id' => $opGroup->id,
                'label' => CRM_Utils_Date::processDate($val),
                'value' => CRM_Utils_Date::processDate($val),
                'name' => $opGroup->name,
                'description' => 'Used for recurring ' . $type,
                'weight' => CRM_Utils_Weight::updateOtherWeights('CRM_Core_DAO_OptionValue', $oldWeight, CRM_Utils_Array::value('weight', $params), $fieldValues),
                'is_active' => 1,
              ];
              $excludeDateList[] = $optionGroupValue['value'];
              CRM_Core_BAO_OptionValue::create($optionGroupValue);
            }
          }
        }

        //Set type for API
        $apiEntityType = explode("_", $type);
        if (!empty($apiEntityType[1])) {
          $apiType = $apiEntityType[1];
        }
        //Delete relations if any from recurring entity tables before inserting new relations for this entity id
        if ($params['entity_id']) {
          //If entity has any pre delete function, consider that first
          if (CRM_Utils_Array::value('pre_delete_func', CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']]) &&
            CRM_Utils_Array::value('helper_class', CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']])
          ) {
            $preDeleteResult = call_user_func_array(CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']]['pre_delete_func'], [$params['entity_id']]);
            if (!empty($preDeleteResult)) {
              call_user_func([CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']]['helper_class'], $preDeleteResult]);
            }
          }
          //Ready to execute delete on entities if it has delete function set
          if (CRM_Utils_Array::value('delete_func', CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']]) &&
            CRM_Utils_Array::value('helper_class', CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']])
          ) {
            //Check if pre delete function has some ids to be deleted
            if (!empty(CRM_Core_BAO_RecurringEntity::$_entitiesToBeDeleted)) {
              foreach (CRM_Core_BAO_RecurringEntity::$_entitiesToBeDeleted as $eid) {
                $result = civicrm_api3(
                  ucfirst(strtolower($apiType)),
                  CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']]['delete_func'],
                  [
                    'sequential' => 1,
                    'id' => $eid,
                  ]
                );
                if ($result['error']) {
                  CRM_Core_Error::statusBounce('Error creating recurring list');
                }
              }
            }
            else {
              $getRelatedEntities = CRM_Core_BAO_RecurringEntity::getEntitiesFor($params['entity_id'], $params['entity_table'], FALSE);
              foreach ($getRelatedEntities as $key => $value) {
                $result = civicrm_api3(
                  ucfirst(strtolower($apiType)),
                  CRM_Core_BAO_RecurringEntity::$_recurringEntityHelper[$params['entity_table']]['delete_func'],
                  [
                    'sequential' => 1,
                    'id' => $value['id'],
                  ]
                );
                if ($result['error']) {
                  CRM_Core_Error::statusBounce('Error creating recurring list');
                }
              }
            }
          }

          // find all entities from the recurring set. At this point we 'll get entities which were not deleted
          // for e.g due to participants being present. We need to delete them from recurring tables anyway.
          $pRepeatingEntities = CRM_Core_BAO_RecurringEntity::getEntitiesFor($params['entity_id'], $params['entity_table']);
          foreach ($pRepeatingEntities as $val) {
            CRM_Core_BAO_RecurringEntity::delEntity($val['id'], $val['table'], TRUE);
          }
        }

        $recursion = new CRM_Core_BAO_RecurringEntity();
        $recursion->dateColumns = $params['dateColumns'];
        $recursion->scheduleId = $actionScheduleObj->id;

        if (!empty($excludeDateList)) {
          $recursion->excludeDates = $excludeDateList;
          $recursion->excludeDateRangeColumns = $params['excludeDateRangeColumns'];
        }
        if (!empty($params['intervalDateColumns'])) {
          $recursion->intervalDateColumns = $params['intervalDateColumns'];
        }
        $recursion->entity_id = $params['entity_id'];
        $recursion->entity_table = $params['entity_table'];
        if (!empty($linkedEntities)) {
          $recursion->linkedEntities = $linkedEntities;
        }

        $recursion->generate();

        $status = ts('Repeat Configuration has been saved');
        CRM_Core_Session::setStatus($status, ts('Saved'), 'success');
      }
    }
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Repeat Entity');
  }

}
