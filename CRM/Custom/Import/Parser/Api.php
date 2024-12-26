<?php

/**
 * Class CRM_Custom_Import_Parser_Api
 */
class CRM_Custom_Import_Parser_Api extends CRM_Import_Parser {

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
   * Main import function.
   *
   * @param array $values
   *   The array of values belonging to this line.
   */
  public function import(array $values): void {
    $rowNumber = (int) $values[array_key_last($values)];
    try {
      $params = $this->getMappedRow($values);
      $params['skipRecentView'] = TRUE;
      $params['check_permissions'] = TRUE;
      $params['entity_id'] = $params['contact_id'];
      civicrm_api3('CustomValue', 'create', $params);
      $this->setImportStatus($rowNumber, 'IMPORTED', '', $params['contact_id']);
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
      $importableFields = $this->getGroupFieldsForImport($customGroupID);
      $this->importableFieldsMetadata = array_merge([
        'do_not_import' => ['title' => ts('- do not import -')],
        'contact_id' => ['title' => ts('Contact ID'), 'name' => 'contact_id', 'type' => CRM_Utils_Type::T_INT, 'options' => FALSE, 'headerPattern' => '/contact?|id$/i'],
        'external_identifier' => ['title' => ts('External Identifier'), 'name' => 'external_identifier', 'type' => CRM_Utils_Type::T_STRING, 'options' => FALSE, 'headerPattern' => '/external\s?id/i'],
      ], $importableFields);
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
      $key = 'custom_' . $values['id'];
      $regexp = preg_replace('/[.,;:!?]/', '', $values['label']);
      $importableFields[$key] = array_merge($values, [
        'name' => $key,
        'title' => $values['label'] ?? NULL,
        'headerPattern' => '/' . preg_quote($regexp, '/') . '/i',
        'import' => 1,
        'custom_field_id' => $values['id'],
        'type' => CRM_Core_BAO_CustomField::dataToType()[$values['data_type']],
        'extends' => $customGroup['extends'],
        'custom_group_id.name' => $customGroup['name'],
        'is_multiple' => $customGroup['is_multiple'],
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

}
