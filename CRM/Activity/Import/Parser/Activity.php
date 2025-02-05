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
use Civi\Api4\Activity;

/**
 * Class to parse activity csv files.
 */
class CRM_Activity_Import_Parser_Activity extends CRM_Import_Parser {

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
    $rowNumber = (int) ($values[array_key_last($values)]);
    // First make sure this is a valid line
    try {
      $params = $this->getMappedRow($values);
      $activityParams = $params['Activity'];
      $targetContactParams = $params['TargetContact'] ?? [];
      $sourceContactParams = $params['SourceContact'] ?? [];

      $activityParams['target_contact_id'] = $this->getContactID($targetContactParams, empty($targetContactParams['id']) ? NULL : (int) $targetContactParams['id'], 'TargetContact', $this->getDedupeRulesForEntity('TargetContact'));

      try {
        $activityParams['source_contact_id'] = $this->getContactID($sourceContactParams, empty($sourceContactParams['id']) ? NULL : (int) $sourceContactParams['id'], 'SourceContact', $this->getDedupeRulesForEntity('SourceContact'));
      }
      catch (CRM_Core_Exception $e) {
        if (empty($activityParams['id'])) {
          $activityParams['source_contact_id'] = CRM_Core_Session::getLoggedInContactID();
        }
      }
      $newActivity = Activity::save()
        ->addRecord($activityParams)
        ->execute()->first();
    }
    catch (CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
      return;
    }
    $this->setImportStatus($rowNumber, 'IMPORTED', '', $newActivity['id']);
  }

  /**
   * @return array
   */
  protected function getRequiredFields(): array {
    return [['activity_type_id', 'activity_date_time']];
  }

  /**
   * Get a list of entities this import supports.
   *
   * @return array
   */
  public function getImportEntities() : array {
    return [
      'Activity' => ['text' => ts('Activity Fields'), 'is_contact' => FALSE, 'entity_field_prefix' => ''],
      'TargetContact' => ['text' => ts('Target Contact Fields'), 'is_contact' => TRUE, 'entity_field_prefix' => 'target_contact.'],
      'SourceContact' => ['text' => ts('Source Contact Fields'), 'is_contact' => TRUE, 'entity_field_prefix' => 'source_contact.'],
    ];
  }

  /**
   * Ensure metadata is loaded.
   *
   * @throws \CRM_Core_Exception
   */
  protected function setFieldMetadata(): void {
    if (empty($this->importableFieldsMetadata)) {
      $fields = ['' => ['title' => '- ' . ts('do not import') . ' -']]
        + (array) Activity::getFields()
          ->addWhere('readonly', '=', FALSE)
          ->addWhere('usage', 'CONTAINS', 'import')
          ->setAction('save')
          ->addOrderBy('title')
          ->execute()->indexBy('name');
      $contactFields = $this->getContactFields('Individual');
      foreach ($contactFields as &$field) {
        // At this stage all fields except those specifically marked are the target_contact.
        $field['entity_instance'] = 'TargetContact';
      }
      $fields['target_contact.id'] = $contactFields['id'];
      $fields['target_contact.id']['entity'] = 'Contact';
      $fields['target_contact.id']['match_rule'] = '*';
      $fields['source_contact.id'] = $fields['target_contact.id'];
      $fields['source_contact.id']['entity_instance'] = 'SourceContact';
      $fields['source_contact.id']['title'] .= ' ' . ts('(match to source contact)');
      $fields['target_contact.id']['title'] .= ' ' . ts('(match to target contact)');
      unset($contactFields['id']);

      $fields += $contactFields;
      $fields['target_contact.external_identifier'] = $contactFields['external_identifier'];
      $fields['target_contact.external_identifier']['title'] = $contactFields['external_identifier']['title'] . ' (target contact)';
      $fields['source_contact.external_identifier'] = $contactFields['external_identifier'];
      $fields['source_contact.external_identifier']['entity_instance'] = 'SourceContact';
      $fields['source_contact.external_identifier']['title'] = $contactFields['external_identifier']['title'] . ' (source contact)';
      unset($fields['external_identifier']);
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
