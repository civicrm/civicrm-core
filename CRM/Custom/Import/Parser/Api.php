<?php

use Civi\Api4\CustomValue;

/**
 * Class CRM_Custom_Import_Parser_Api
 */
class CRM_Custom_Import_Parser_Api extends CRM_Import_Parser {

  protected string $baseEntity = 'Contact';

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
      'custom_field_import' => [
        'id' => 'custom_field_import',
        'name' => 'custom_field_import',
        'label' => ts('Multiple Value Custom Field Import'),
        'entity' => 'Contact',
        'url' => 'civicrm/import/custom',
      ],
    ];
  }

  /**
   * Get a list of entities this import supports.
   *
   * @return array
   */
  public function getImportEntities() : array {
    return [
      $this->getGroupName() => [
        'text' => ts('Custom Fields'),
        'is_contact' => FALSE,
        'required_fields_update' => [],
        'required_fields_create' => [],
        'is_base_entity' => TRUE,
        'supports_multiple' => FALSE,
        'is_required' => TRUE,
        // For now we stick with the action selected on the DataSource page.
        'actions' => [['id' => 'create', 'text' => ts('Create'), 'description' => ts('Skip if already exists')]],
        'default_action' => 'create',
        'entity_name' => $this->getGroupName(),
        'entity_title' => $this->getGroupTitle(),
        'entity_field_prefix' => '',
        'selected' => ['action' => 'create'],
      ],
      'Contact' => [
        'text' => ts('Contact Fields'),
        'is_contact' => TRUE,
        'entity_field_prefix' => 'Contact.',
        'unique_fields' => ['external_identifier', 'id'],
        'supports_multiple' => FALSE,
        'actions' => $this->getActions(['select', 'update', 'save']),
        'selected' => [
          'action' => 'select',
          'contact_type' => $this->getSubmittedValue('contactType'),
          'dedupe_rule' => $this->getDedupeRule($this->getContactType())['name'],
        ],
        'default_action' => 'select',
        'entity_name' => 'Contact',
        'entity_title' => ts('Contact'),
      ],
    ];
  }

  /**
   * Main import function.
   *
   * @param array $values
   *   The array of values belonging to this line.
   */
  public function import(array $values): void {
    $values = array_values($values);
    $rowNumber = (int) $values[array_key_last($values)];
    try {
      $params = $this->getMappedRow($values);
      $contactParams = $params['Contact'];
      $groupName = $this->getGroupName();
      $params = $params[$groupName];
      $params['skipRecentView'] = TRUE;
      $params['entity_id'] = $this->getContactID($contactParams, $contactParams['id'] ?? NULL, 'Contact');

      CustomValue::create($groupName)
        ->setValues($params)
        ->execute();
      $this->setImportStatus($rowNumber, 'IMPORTED', '', $params['entity_id']);
    }
    catch (CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage(), $params['contact_id'] ?? NULL);
    }
  }

  /**
   * Set the import metadata.
   */
  public function setFieldMetadata(): void {
    if (!$this->importableFieldsMetadata) {
      $customGroupID = $this->getCustomGroupID();
      $fields = ['' => ['title' => ts('- do not import -')]];
      $importableFields = CRM_Utils_Array::prefixKeys($this->getGroupFieldsForImport($customGroupID), $this->getGroupName() . '.');
      $this->importableFieldsMetadata = $fields + $importableFields + $this->getContactFields($this->getContactType(), 'Contact', TRUE);
    }
  }

  /**
   * Get the required fields.
   *
   * @return array
   */
  public function getRequiredFields(): array {
    return [['contact_id'], ['external_identifier']];
  }

  /**
   * Return the field ids and names (with groups) for import purpose.
   *
   * @param int $customGroupID
   *   Custom group ID.
   *
   * @return array
   */
  private function getGroupFieldsForImport(int $customGroupID): array {
    $importableFields = [];
    $customGroup = CRM_Core_BAO_CustomGroup::getGroup(['id' => $customGroupID]);

    foreach ($customGroup['fields'] as $values) {
      if ($values['data_type'] === 'File') {
        continue;
      }
      /* generate the key for the fields array */
      $regexp = preg_replace('/[.,;:!?]/', '', $values['label']);
      $importableFields[$values['name']] = array_merge($values, [
        'name' => $values['name'],
        'title' => $values['label'] ?? NULL,
        'headerPattern' => '/' . preg_quote($regexp, '/') . '/i',
        'import' => 1,
        'custom_field_id' => $values['id'],
        'type' => CRM_Core_BAO_CustomField::dataToType()[$values['data_type']],
        'extends' => $customGroup['extends'],
        'custom_group_id.name' => $customGroup['name'],
        'is_multiple' => $customGroup['is_multiple'],
        'entity' => $customGroup['name'],
      ]);
    }
    return $importableFields;
  }

  /**
   * @return int
   */
  private function getCustomGroupID(): int {
    return (int) $this->getSubmittedValue('multipleCustomData');
  }

  /**
   * @return mixed
   */
  public function getGroupName(): mixed {
    return CRM_Core_BAO_CustomGroup::getGroup(['id' => $this->getCustomGroupID()])['name'];
  }

  /**
   * @return mixed
   */
  public function getGroupTitle(): mixed {
    return CRM_Core_BAO_CustomGroup::getGroup(['id' => $this->getCustomGroupID()])['title'];
  }

}
