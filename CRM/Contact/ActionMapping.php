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

use Civi\ActionSchedule\RecipientBuilder;

/**
 * Class CRM_Contact_ActionMapping
 *
 * This defines the scheduled-reminder functionality for contact
 * entities. It is useful for, e.g., sending a reminder based on
 * birth date, modification date, or other custom dates on
 * the contact record.
 */
class CRM_Contact_ActionMapping extends \Civi\ActionSchedule\Mapping {

  /**
   * The value for civicrm_action_schedule.mapping_id which identifies the
   * "Contact" mapping.
   *
   * Note: This value is chosen to match legacy DB IDs.
   */
  const CONTACT_MAPPING_ID = 6;

  /**
   * Register Contact-related action mappings.
   *
   * @param \Civi\ActionSchedule\Event\MappingRegisterEvent $registrations
   */
  public static function onRegisterActionMappings(\Civi\ActionSchedule\Event\MappingRegisterEvent $registrations) {
    $registrations->register(CRM_Contact_ActionMapping::create([
      'id' => CRM_Contact_ActionMapping::CONTACT_MAPPING_ID,
      'entity' => 'civicrm_contact',
      'entity_label' => ts('Contact'),
      'entity_value' => 'civicrm_contact',
      'entity_value_label' => ts('Date Field'),
      'entity_status' => 'contact_date_reminder_options',
      'entity_status_label' => ts('Annual Options'),
      'entity_date_start' => 'date_field',
    ]));
  }

  private $contactDateFields = [
    'birth_date',
    'created_date',
    'modified_date',
  ];

  /**
   * Determine whether a schedule based on this mapping is sufficiently
   * complete.
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   * @return array
   *   Array (string $code => string $message).
   *   List of error messages.
   */
  public function validateSchedule($schedule) {
    $errors = [];
    if (CRM_Utils_System::isNull($schedule->entity_value) || $schedule->entity_value === '0') {
      $errors['entity'] = ts('Please select a specific date field.');
    }
    elseif (count(CRM_Utils_Array::explodePadded($schedule->entity_value)) > 1) {
      $errors['entity'] = ts('You may only select one contact field per reminder');
    }
    elseif (CRM_Utils_System::isNull($schedule->entity_status) || $schedule->entity_status === '0') {
      $errors['entity'] = ts('Please select whether the reminder is sent each year.');
    }

    return $errors;
  }

  /**
   * Generate a query to locate recipients who match the given
   * schedule.
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   *   The schedule as configured by the administrator.
   * @param string $phase
   *   See, e.g., RecipientBuilder::PHASE_RELATION_FIRST.
   * @param array $defaultParams
   *
   * @return \CRM_Utils_SQL_Select
   * @throws \CRM_Core_Exception
   * @see RecipientBuilder
   */
  public function createQuery($schedule, $phase, $defaultParams) {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($schedule->entity_value);
    $selectedStatuses = (array) \CRM_Utils_Array::explodePadded($schedule->entity_status);

    // FIXME: This assumes that $values only has one field, but UI shows multiselect.
    // Properly supporting multiselect would require total rewrite of this function.
    if (count($selectedValues) != 1 || !isset($selectedValues[0])) {
      throw new \CRM_Core_Exception("Error: Scheduled reminders may only have one contact field.");
    }
    elseif (in_array($selectedValues[0], $this->contactDateFields)) {
      $dateDBField = $selectedValues[0];
      $query = \CRM_Utils_SQL_Select::from("{$this->entity} e")->param($defaultParams);
      $query->param([
        'casAddlCheckFrom' => 'civicrm_contact e',
        'casContactIdField' => 'e.id',
        'casEntityIdField' => 'e.id',
        'casContactTableAlias' => 'e',
      ]);
      $query->where('e.is_deleted = 0 AND e.is_deceased = 0');
    }
    else {
      //custom field
      $customFieldParams = ['id' => substr($selectedValues[0], 7)];
      $customGroup = $customField = [];
      \CRM_Core_BAO_CustomField::retrieve($customFieldParams, $customField);
      $dateDBField = $customField['column_name'];
      $customGroupParams = ['id' => $customField['custom_group_id'], $customGroup];
      \CRM_Core_BAO_CustomGroup::retrieve($customGroupParams, $customGroup);
      $query = \CRM_Utils_SQL_Select::from("{$customGroup['table_name']} e")->param($defaultParams);
      $query->param([
        'casAddlCheckFrom' => "{$customGroup['table_name']} e",
        'casContactIdField' => 'e.entity_id',
        'casEntityIdField' => 'e.id',
        'casContactTableAlias' => NULL,
      ]);
      $query->where('1'); // possible to have no "where" in this case
    }

    $query['casDateField'] = 'e.' . $dateDBField;

    if (in_array(2, $selectedStatuses)) {
      $query['casAnniversaryMode'] = 1;
      $query['casDateField'] = 'DATE_ADD(' . $query['casDateField'] . ', INTERVAL ROUND(DATEDIFF(DATE(' . $query['casNow'] . '), ' . $query['casDateField'] . ') / 365) YEAR)';
    }

    return $query;
  }

}
