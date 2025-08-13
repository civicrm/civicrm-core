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

namespace Civi\Import;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
use Civi\Api4\Activity;
use Civi\Api4\Contact;

/**
 * Class to parse activity csv files.
 */
class ActivityParser extends ImportParser {

  protected string $baseEntity = 'Activity';

  /**
   * Get information about the provided job.
   *  - name
   *  - id (generally the same as name)
   *  - label
   *
   *  e.g. ['activity_import' => ['id' => 'activity_import', 'label' => ts('Activity Import'), 'name' => 'activity_import']]
   *
   * @return array
   */
  public static function getUserJobInfo(): array {
    return [
      'activity_import' => [
        'id' => 'activity_import',
        'name' => 'activity_import',
        'label' => ts('Activity Import'),
        'entity' => 'Activity',
        'url' => 'civicrm/import/activity',
      ],
    ];
  }

  /**
   * The initializer code, called before the processing.
   *
   * @throws \CRM_Core_Exception
   */
  public function init(): void {
    $this->setFieldMetadata();
  }

  /**
   * Handle the values in import mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   */
  public function import(array $values): void {
    $values = array_values($values);
    $rowNumber = (int) ($values[array_key_last($values)]);
    // First make sure this is a valid line
    try {
      $params = $this->getMappedRow($values);
      $activityParams = $params['Activity'];
      $targetContactParams = $params['TargetContact'] ?? [];
      $sourceContactParams = $params['SourceContact'] ?? [];
      $assigneeContactParams = $params['AssigneeContact'] ?? [];
      if (array_keys($targetContactParams) === ['email_primary.email']) {
        $targetContactParams['contact_type'] = 'Individual';
      }
      $activityParams['target_contact_id'] = $this->getContactID($targetContactParams, empty($targetContactParams['id']) ? NULL : (int) $targetContactParams['id'], 'TargetContact', $this->getDedupeRulesForEntity('TargetContact'));
      $activityParams['assignee_contact_id'] = $this->getContactID($assigneeContactParams, empty($assigneeContactParams['id']) ? NULL : (int) $assigneeContactParams['id'], 'AssigneeContact', $this->getDedupeRulesForEntity('AssigneeContact'));

      try {
        $activityParams['source_contact_id'] = $this->getContactID($sourceContactParams, empty($sourceContactParams['id']) ? NULL : (int) $sourceContactParams['id'], 'SourceContact', $this->getDedupeRulesForEntity('SourceContact'));
      }
      catch (\CRM_Core_Exception $e) {
      }
      if (empty($activityParams['id']) && empty($activityParams['source_contact_id'])) {
        $activityParams['source_contact_id'] = \CRM_Core_Session::getLoggedInContactID();
      }
      $newActivity = Activity::save()
        ->addRecord($activityParams)
        ->execute()->first();
    }
    catch (\CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
      return;
    }
    $this->setImportStatus($rowNumber, 'IMPORTED', '', $newActivity['id']);
  }

  /**
   * @return array
   */
  protected function getRequiredFields(): array {
    return [['Activity.activity_type_id', 'Activity.activity_date_time']];
  }

  /**
   * Get a list of entities this import supports.
   *
   * @return array
   */
  public function getImportEntities() : array {
    return [
      'Activity' => [
        'text' => ts('Activity Fields'),
        'entity_title' => ts('Activity'),
        'entity_type' => 'Activity',
        'actions' => [
          ['id' => 'save', 'text' => ts('Create or Update using ID'), 'description' => ts('Skip if no match found')],
        ],
        'selected' => [
          'action' => 'save',
        ],
        'default_action' => 'save',
        'entity_name' => 'Activity',
      ],
      'TargetContact' => [
        'text' => ts('Target Contact Fields'),
        'entity_title' => ts('Target Contact'),
        'entity_type' => 'Contact',
        'unique_fields' => ['external_identifier', 'id'],
        'supports_multiple' => TRUE,
        'actions' => $this->getActions(['select', 'ignore', 'update']),
        'selected' => [
          'action' => 'select',
          'contact_type' => NULL,
          'dedupe_rule' => ['unique_identifier_match'],
        ],
        'default_action' => 'select',
        'entity_name' => 'TargetContact',
      ],
      'SourceContact' => [
        'text' => ts('Source Contact Fields'),
        'entity_title' => ts('Source Contact'),
        'entity_type' => 'Contact',
        'unique_fields' => ['external_identifier', 'id'],
        'supports_multiple' => FALSE,
        'actions' => $this->isUpdateExisting() ? $this->getActions(['ignore']) : $this->getActions(['ignore', 'select', 'update', 'save']),
        'selected' => [
          'action' => 'ignore',
          'contact_type' => 'Individual',
        ],
        'default_action' => 'select',
        'entity_name' => 'SourceContact',
      ],
      'AssigneeContact' => [
        'text' => ts('assignee Contact Fields'),
        'entity_title' => ts('Assignee Contact'),
        'entity_type' => 'Contact',
        'unique_fields' => ['external_identifier', 'id'],
        'supports_multiple' => TRUE,
        'actions' => $this->getActions(['select', 'ignore']),
        'selected' => [
          'action' => 'ignore',
          'contact_type' => 'Individual',
        ],
        'default_action' => 'ignore',
        'entity_name' => 'AssigneeContact',
      ],
    ];
  }

  /**
   * Ensure metadata is loaded.
   *
   * @throws \CRM_Core_Exception
   */
  protected function setFieldMetadata(): void {
    if (empty($this->importableFieldsMetadata)) {
      $fields = ['' => ['title' => '- ' . ts('do not import') . ' -']];
      $activityFields = (array) Activity::getFields()
        ->addWhere('readonly', '=', FALSE)
        ->addWhere('usage', 'CONTAINS', 'import')
        ->setAction('save')
        ->addOrderBy('title')
        ->execute()->indexBy('name');
      foreach ($activityFields as $fieldName => $field) {
        $field['entity_instance'] = 'Activity';
        $field['entity_prefix'] = 'Activity.';
        $fields['Activity.' . $fieldName] = $field;
      }
      $idSchema = Contact::getFields(FALSE)
        ->addWhere('name', '=', 'id')
        ->execute()->single();
      // For other entities contact_id is part of the main entity - that doesn't work here so
      // hacking the ID in since the function won't add it.
      $contactFields = [];
      foreach (['SourceContact', 'TargetContact', 'AssigneeContact'] as $activityContactType) {
        $matchText = ' ' . ts('(match to %1)', [1 => $activityContactType]);
        $contactFields[$activityContactType . '.id'] = $idSchema;
        $contactFields[$activityContactType . '.id']['title'] .= $matchText;
        $contactFields[$activityContactType . '.id']['match_rule'] = '*';
        $contactFields[$activityContactType . '.id']['entity_instance'] = $activityContactType;
        $contactFields[$activityContactType . '.id']['contact_type'] = ['Individual', 'Organization', 'Household'];
        $contactFields += $this->getContactFields('Individual', $activityContactType);
      }

      $fields += $contactFields;
      $this->importableFieldsMetadata = $fields;
    }
  }

  /**
   * Get the metadata field for which importable fields does not key the actual field name.
   *
   * This is intended as a transitional function to handle fields like
   * (and probably only) target_contact.email_primary.email when the
   * declared field is 'email_primary.email'
   *
   * @return string[]
   */
  protected function getOddlyMappedMetadataFields(): array {
    $contactSpecificFields = [];
    foreach ($this->importableFieldsMetadata as $index => $field) {
      if (!str_starts_with($index, 'target_contact.')
        && !str_starts_with($index, 'source_contact')
        && ($field['entity'] ?? '') === 'Contact') {
        $contactSpecificFields['target_contact.' . $index] = $index;
        $contactSpecificFields['source_contact.' . $index] = $index;
      }
    }
    return $contactSpecificFields;
  }

}
