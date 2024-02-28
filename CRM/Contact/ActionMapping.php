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
 * This defines the scheduled-reminder functionality for contact
 * entities. It is useful for, e.g., sending a reminder based on
 * birth date, modification date, or other custom dates on
 * the contact record.
 */
class CRM_Contact_ActionMapping extends \Civi\ActionSchedule\MappingBase {

  /**
   * Note: This value is an integer for legacy reasons; but going forward any new
   * action mapping classes should return a string from `getId` instead of using a constant.
   */
  const CONTACT_MAPPING_ID = 6;

  public function getId() {
    return self::CONTACT_MAPPING_ID;
  }

  public function getName(): string {
    return 'contact';
  }

  public function getEntityName(): string {
    return 'Contact';
  }

  public function modifyApiSpec(\Civi\Api4\Service\Spec\RequestSpec $spec) {
    $spec->getFieldByName('entity_value')
      ->setLabel(ts('Date Field'))
      ->setInputAttr('multiple', FALSE);
    $spec->getFieldByName('entity_status')
      ->setLabel(ts('Annual Options'))
      ->setInputAttr('multiple', FALSE)
      ->setRequired(TRUE);
  }

  public function getValueLabels(): array {
    $filter = ['extends' => 'Contact', 'is_multiple' => FALSE, 'is_active' => TRUE];
    $contactCustomGroups = \CRM_Core_BAO_CustomGroup::getAll($filter);
    $dateFields = [
      'birth_date' => ts('Birth Date'),
      'created_date' => ts('Created Date'),
      'modified_date' => ts('Modified Date'),
    ];
    foreach ($contactCustomGroups as $customGroup) {
      foreach ($customGroup['fields'] as $field) {
        if ($field['data_type'] == 'Date') {
          $dateFields["custom_{$field['id']}"] = $field['label'];
        }
      }
    }
    return $dateFields;
  }

  public function getStatusLabels(?array $entityValue): array {
    return CRM_Core_OptionGroup::values('contact_date_reminder_options');
  }

  public function getDateFields(?array $entityValue = NULL): array {
    return [
      'date_field' => ts('Date Field'),
    ];
  }

  private $contactDateFields = [
    'birth_date',
    'created_date',
    'modified_date',
  ];

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
  public function createQuery($schedule, $phase, $defaultParams): CRM_Utils_SQL_Select {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($schedule->entity_value);
    $selectedStatuses = (array) \CRM_Utils_Array::explodePadded($schedule->entity_status);

    // Only one value is allowed for this mapping type.
    // The form and API both enforce this, so this error should never happen.
    if (count($selectedValues) != 1 || !isset($selectedValues[0])) {
      throw new \CRM_Core_Exception("Error: Scheduled reminders may only have one contact field.");
    }
    elseif (in_array($selectedValues[0], $this->contactDateFields)) {
      $dateDBField = $selectedValues[0];
      $query = \CRM_Utils_SQL_Select::from('civicrm_contact e')->param($defaultParams);
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
      // possible to have no "where" in this case
      $query->where('1');
    }

    $query['casDateField'] = 'e.' . $dateDBField;

    if (in_array(2, $selectedStatuses)) {
      $query['casAnniversaryMode'] = 1;
      $query['casDateField'] = 'DATE_ADD(' . $query['casDateField'] . ', INTERVAL ROUND(DATEDIFF(DATE(' . $query['casNow'] . '), ' . $query['casDateField'] . ') / 365) YEAR)';
    }

    return $query;
  }

}
