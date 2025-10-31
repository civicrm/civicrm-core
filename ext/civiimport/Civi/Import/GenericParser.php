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

/**
 * class to parse import csv files
 */
class GenericParser extends ImportParser {

  protected string $baseEntity;

  public function getBaseEntity(): string {
    if (!isset($this->baseEntity)) {
      $this->baseEntity = $this->getUserJob()['metadata']['base_entity'] ?? '';
    }
    return $this->baseEntity;
  }

  public function setBaseEntity(string $entity) {
    $this->baseEntity = $entity;
  }

  /**
   * Get information about the provided job.
   *
   *  - name
   *  - id (generally the same as name)
   *  - label
   *
   * @return array
   */
  public static function getUserJobInfo(): array {
    return [
      'import_generic' => [
        'id' => 'import_generic',
        'name' => 'import_generic',
        'label' => ts('Import'),
        'entity' => '%',
        'url' => 'civicrm/import/%',
      ],
    ];
  }

  /**
   * Set field metadata.
   */
  protected function setFieldMetadata(): void {
    if (empty($this->importableFieldsMetadata)) {
      $fields = ['' => ['title' => ts('- do not import -')]];
      foreach ($this->getImportFieldsForEntity($this->getBaseEntity()) as $field) {
        $field['entity_instance'] = $this->getBaseEntity();
        $field['entity_prefix'] = 'Contribution.';
        $fields[$this->getBaseEntity() . '.' . $field['name']] = $field;
      }
      if (isset($fields[$this->getBaseEntity() . '.' . 'contact_id'])) {
        $fields += $this->getContactFields($this->getContactType(), 'Contact');
        unset($fields[$this->getBaseEntity() . '.contact_id']);
      }
      $this->importableFieldsMetadata = $fields;
    }
  }

  public function getImportableFieldsMetadata():array {
    if (empty($this->importableFieldsMetadata)) {
      $this->setFieldMetadata();
    }
    return parent::getImportableFieldsMetadata();
  }

  /**
   * Get a list of entities this import supports.
   *
   * @return array
   */
  public function getAvailableImportEntities() : array {
    $fields = $this->getImportableFieldsMetadata();
    $entities = [
      $this->getBaseEntity() => [
        'text' => $this->getBaseEntity(),
        'required_fields_update' => $this->getRequiredFieldsForMatch(),
        'required_fields_create' => $this->getRequiredFieldsForCreate(),
        'is_base_entity' => TRUE,
        'supports_multiple' => FALSE,
        'is_required' => TRUE,
        'actions' => [
          ['id' => 'update', 'text' => ts('Update existing'), 'description' => ts('Skip if no match found')],
          ['id' => 'create', 'text' => ts('Create'), 'description' => ts('Skip if already exists')],
        ],
        'default_action' => 'create',
        'entity_name' => $this->getBaseEntity(),
        'entity_title' => $this->getBaseEntity(),
        'selected' => ['action' => 'create'],
      ],
    ];
    // Just add the most basic contact entity for now.
    // We could run of FKs but we also need to consider the complexity of the UI
    // for a new-to-civi task like importing.
    if (isset($fields['Contact.id'])) {
      $entities['Contact'] = [
        'text' => ts('Contact Fields'),
        'unique_fields' => ['external_identifier', 'id'],
        'entity_type' => 'Contact',
        'supports_multiple' => FALSE,
        'actions' => $this->isUpdateExisting() ? $this->getActions(['ignore', 'update']) : $this->getActions(['select', 'update', 'save']),
        'selected' => [
          'action' => $this->isUpdateExisting() ? 'ignore' : 'select',
          'contact_type' => 'Individual',
          'dedupe_rule' => (array) $this->getDedupeRule('Individual')['name'],
        ],
        'default_action' => 'select',
        'entity_name' => 'Contact',
        'entity_title' => ts('Contact'),
      ];
    }
    return $entities;
  }

  /**
   * Get required fields to match a contribution.
   *
   * @return array
   */
  public function getRequiredFieldsForMatch(): array {
    return [[$this->getBaseEntity() . '.id']];
  }

  /**
   * Get required fields to create a contribution.
   *
   * @return array
   */
  protected function getRequiredFieldsForCreate(): array {
    $fields = [];
    foreach ($this->getImportableFieldsMetadata() as $field) {
      if (!empty($field['required']) && $field['entity'] === $this->getBaseEntity()
        // We should probably require the fields required for contact ID
        // but for now we leave this to fail on import.
        && $field['name'] !== 'contact_id'
      ) {
        $fields[] = $this->getBaseEntity() . '.' . $field['name'];
      }
    }
    return $fields;
  }

  /**
   * Handle the values in import mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return int
   *   the result of this processing - which is ignored
   */
  public function import(array $values) {
    $values = array_values($values);
    $rowNumber = (int) ($values[array_key_last($values)]);
    try {
      $params = $this->getMappedRow($values);
      $this->removeEmptyValues($params);
      \CRM_Utils_Hook::importAlterMappedRow('import', $this->getBaseEntity() . '_import', $params, $values, $this->getUserJobID());

      $existing = !isset($params[$this->getBaseEntity()]['id']) ? [] : civicrm_api4($this->getBaseEntity(), 'get', [
        'where' => [
          ['id', '=', $params[$this->getBaseEntity()]['id']],
        ],
      ])->single();
      if (!empty($params['Contact'])) {
        $params['Contact']['id'] = $this->getContactID($params['Contact'] ?? [], ($params['Contact']['id'] ?? $existing['contact_id'] ?? NULL), 'Contact', $this->getDedupeRulesForEntity('Contact'));
        $params[$this->getBaseEntity()]['contact_id'] = $this->saveContact('Contact', $params['Contact'] ?? []) ?: $params['Contact']['id'];
      }
      $entity = civicrm_api4($this->baseEntity, 'save', [
        'records' => [$params[$this->baseEntity]],
      ])->first();
      $this->setImportStatus($rowNumber, 'IMPORTED', '', $entity['id']);
      return \CRM_Import_Parser::VALID;

    }
    catch (\CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
      return \CRM_Import_Parser::ERROR;
    }
  }

}
