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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
   * @param $activity_type_id int This matches up to the option_value 'value' column in the database.
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
   * @param $activity_type_id int This matches up to the option_value 'value' column in the database.
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
        'machineName' => CRM_Utils_Array::value($activity_type_id, $machineNames),
        'displayLabel' => CRM_Utils_Array::value($activity_type_id, $displayLabels),
        'id' => $activity_type_id,
      ];
    }
  }

}
