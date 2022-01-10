<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class is a wrapper that moves some boilerplate code out of the Form class and helps remove some ambiguity with name and label.
 */
class CRM_Activity_BAO_ActivityType {

  /**
   * key/value pair for this activity type
   *
   * @var array
   *   machineName The internal name for lookups - matches up to option_value 'name' column in the database
   *   displayLabel The label used for display/output - matches up to option_value 'label' column in the database
   *   id The value used to initialize this object - matches up to the option_value 'value' column in the database
   */
  protected $_activityType = [
    'machineName' => NULL,
    'displayLabel' => NULL,
    'id' => NULL,
  ];

  /**
   * Constructor
   *
   * @param int $activity_type_id This matches up to the option_value 'value' column in the database.
   */
  public function __construct($activity_type_id) {
    $this->setActivityType($activity_type_id);
  }

  /**
   * Get the key/value pair representing this activity type.
   *
   * @return array
   * @see $this->_activityType
   */
  public function getActivityType() {
    return $this->_activityType;
  }

  /**
   * Look up the key/value pair representing this activity type from the id.
   * Generally called from constructor.
   *
   * @param int $activity_type_id This matches up to the option_value 'value' column in the database.
   */
  public function setActivityType($activity_type_id) {
    if ($activity_type_id && is_numeric($activity_type_id)) {

      /*
       * These are pulled from CRM_Activity_Form_Activity.
       * To avoid unexpectedly changing things like introducing hidden
       * business logic or changing permission checks I've kept it using
       * the same function call. It may or may not be desired to have
       * that but this at least doesn't introduce anything that wasn't
       * there before.
       */
      $machineNames = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, 'AND v.value = ' . $activity_type_id, 'name');
      $displayLabels = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, 'AND v.value = ' . $activity_type_id, 'label');

      $this->_activityType = [
        'machineName' => $machineNames[$activity_type_id] ?? NULL,
        'displayLabel' => $displayLabels[$activity_type_id] ?? NULL,
        'id' => $activity_type_id,
      ];
    }
  }

}
