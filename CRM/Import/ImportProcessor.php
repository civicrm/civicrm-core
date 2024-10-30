<?php

use Civi\Api4\Mapping;
use Civi\Api4\MappingField;

/**
 * Class CRM_Import_ImportProcessor.
 *
 * Import processor class. This is intended to provide a sanitising wrapper around
 * the form-oriented import classes. In particular it is intended to provide a clear translation
 * between the saved mapping field format and the quick form & parser formats.
 *
 * In the first instance this is only being used in unit tests but the intent is to migrate
 * to it on a trajectory similar to the ExportProcessor so it is not in the tests.
 */
class CRM_Import_ImportProcessor {

  /**
   * An array of fields in the format used in the table civicrm_mapping_field.
   *
   * @var array
   */
  protected $mappingFields = [];

  /**
   * @var array
   */
  protected $metadata = [];

  /**
   * Id of the created user job.
   *
   * @var int
   */
  protected $userJobID;

  /**
   * @return int
   */
  public function getUserJobID(): int {
    return $this->userJobID;
  }

  /**
   * @param int $userJobID
   */
  public function setUserJobID(int $userJobID): void {
    $this->userJobID = $userJobID;
  }

  /**
   * Metadata keyed by field title.
   *
   * @var array
   */
  protected $metadataByTitle = [];

  /**
   * Get contact type being imported.
   *
   * @var string
   */
  protected $contactType;

  /**
   * Get contact sub type being imported.
   *
   * @var string
   */
  protected $contactSubType;

  /**
   * Array of valid relationships for the contact type & subtype.
   *
   * @var array
   */
  protected $validRelationships = [];

  /**
   * Name of  the form.
   *
   * Used for js for quick form.
   *
   * @var string
   */
  protected $formName;

  /**
   * @return string
   */
  public function getFormName(): string {
    return $this->formName;
  }

  /**
   * @param string $formName
   */
  public function setFormName(string $formName) {
    $this->formName = $formName;
  }

  /**
   * @return array
   */
  public function getValidRelationships(): array {
    if (!isset($this->validRelationships[$this->getContactType() . '_' . $this->getContactSubType()])) {
      //Relationship importables
      $relations = CRM_Contact_BAO_Relationship::getContactRelationshipType(
        NULL, NULL, NULL, $this->getContactType(),
        FALSE, 'label', TRUE, $this->getContactSubType()
      );
      asort($relations);
      $this->setValidRelationships($relations);
    }
    return $this->validRelationships[$this->getContactType() . '_' . $this->getContactSubType()];
  }

  /**
   * @param array $validRelationships
   */
  public function setValidRelationships(array $validRelationships) {
    $this->validRelationships[$this->getContactType() . '_' . $this->getContactSubType()] = $validRelationships;
  }

  /**
   * Get contact subtype for import.
   *
   * @return string
   */
  public function getContactSubType(): string {
    return $this->contactSubType ?? '';
  }

  /**
   * Set contact subtype for import.
   *
   * @param string $contactSubType
   */
  public function setContactSubType($contactSubType) {
    $this->contactSubType = (string) $contactSubType;
  }

  /**
   * Saved Mapping ID.
   *
   * @var int
   */
  protected $mappingID;

  /**
   * @return array
   */
  public function getMetadata(): array {
    return $this->metadata;
  }

  /**
   * Setting for metadata.
   *
   * We wrangle the label for custom fields to include the label since the
   * metadata  trait presents it in a more 'pure' form but the label is  appended for importing.
   *
   * @param array $metadata
   *
   * @throws \CRM_Core_Exception
   */
  public function setMetadata(array $metadata) {
    foreach ($metadata as $index => $field) {
      if (!empty($field['custom_field_id'])) {
        $fieldDetails = CRM_Core_BAO_CustomField::getField($field['custom_field_id']);
        // The 'label' format for import is custom group title :: custom name title
        $metadata[$index]['name'] = $index;
        $metadata[$index]['title'] .= ' :: ' . $fieldDetails['custom_group']['title'];
      }
    }
    $this->metadata = $metadata;
  }

  /**
   * @return int
   */
  public function getMappingID(): int {
    return $this->mappingID;
  }

  /**
   * @param int $mappingID
   */
  public function setMappingID(int $mappingID) {
    $this->mappingID = $mappingID;
  }

  /**
   * Get the contact type for the import.
   *
   * @return string
   */
  public function getContactType(): string {
    return $this->contactType;
  }

  /**
   * @param string $contactType
   */
  public function setContactType(string $contactType) {
    $this->contactType = $contactType;
  }

  /**
   * Set the contact type  according to the constant.
   *
   * @deprecated
   *
   * @param int $contactTypeKey
   */
  public function setContactTypeByConstant($contactTypeKey) {
    CRM_Core_Error::deprecatedFunctionWarning('no replacement');
    $constantTypeMap = [
      'Individual' => 'Individual',
      'Household' => 'Household',
      'Organization' => 'Organization',
    ];
    $this->contactType = $constantTypeMap[$contactTypeKey];
  }

  /**
   * Get Mapping Fields.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function getMappingFields(): array {
    if (empty($this->mappingFields) && !empty($this->getMappingID())) {
      $this->loadSavedMapping();
    }
    return $this->mappingFields;
  }

  /**
   * Set mapping fields.
   *
   * We do a little cleanup here too.
   *
   * We ensure that column numbers are set and that the fields are ordered by them.
   *
   * This would mean the fields could be loaded unsorted.
   *
   * @param array $mappingFields
   */
  public function setMappingFields(array $mappingFields) {
    $i = 0;
    foreach ($mappingFields as &$mappingField) {
      if (!isset($mappingField['column_number'])) {
        $mappingField['column_number'] = $i;
      }
      if ($mappingField['column_number'] > $i) {
        $i = $mappingField['column_number'];
      }
      $i++;
    }
    $this->mappingFields = $this->rekeyBySortedColumnNumbers($mappingFields);
  }

  /**
   * Get the names of the mapped fields.
   *
   * @throws \CRM_Core_Exception
   */
  public function getFieldNames() {
    return CRM_Utils_Array::collect('name', $this->getMappingFields());
  }

  /**
   * Get the field name for the given column.
   *
   * @param int $columnNumber
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function getFieldName($columnNumber) {
    return $this->getFieldNames()[$columnNumber];
  }

  /**
   * Get the field name for the given column.
   *
   * @param int $columnNumber
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function getRelationshipKey($columnNumber) {
    $field = $this->getMappingFields()[$columnNumber];
    return empty($field['relationship_type_id']) ? NULL : $field['relationship_type_id'] . '_' . $field['relationship_direction'];
  }

  /**
   * Get relationship key only if it is valid.
   *
   * @param int $columnNumber
   *
   * @return string|null
   *
   * @throws \CRM_Core_Exception
   */
  public function getValidRelationshipKey($columnNumber) {
    $key = $this->getRelationshipKey($columnNumber);
    return $this->isValidRelationshipKey($key) ? $key : NULL;
  }

  /**
   * Get the IM Provider ID.
   *
   * @param int $columnNumber
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  public function getIMProviderID($columnNumber) {
    return $this->getMappingFields()[$columnNumber]['im_provider_id'] ?? NULL;
  }

  /**
   * Get the Phone Type
   *
   * @param int $columnNumber
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  public function getPhoneTypeID($columnNumber) {
    return $this->getMappingFields()[$columnNumber]['phone_type_id'] ?? NULL;
  }

  /**
   * Get the Website Type
   *
   * @param int $columnNumber
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  public function getWebsiteTypeID($columnNumber) {
    return $this->getMappingFields()[$columnNumber]['website_type_id'] ?? NULL;
  }

  /**
   * Get the Location Type
   *
   * Returning 0 rather than null is historical.
   *
   * @param int $columnNumber
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  public function getLocationTypeID($columnNumber) {
    return $this->getMappingFields()[$columnNumber]['location_type_id'] ?? 0;
  }

  /**
   * Get the IM or Phone type.
   *
   * We have a field that would be the 'relevant' type - which could be either.
   *
   * @param int $columnNumber
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  public function getPhoneOrIMTypeID($columnNumber) {
    return $this->getIMProviderID($columnNumber) ?? $this->getPhoneTypeID($columnNumber);
  }

  /**
   * Get the location types of the mapped fields.
   *
   * @throws \CRM_Core_Exception
   */
  public function getFieldLocationTypes() {
    return CRM_Utils_Array::collect('location_type_id', $this->getMappingFields());
  }

  /**
   * Get the phone types of the mapped fields.
   *
   * @throws \CRM_Core_Exception
   */
  public function getFieldPhoneTypes() {
    return CRM_Utils_Array::collect('phone_type_id', $this->getMappingFields());
  }

  /**
   * Get the names of the im_provider fields.
   *
   * @throws \CRM_Core_Exception
   */
  public function getFieldIMProviderTypes() {
    return CRM_Utils_Array::collect('im_provider_id', $this->getMappingFields());
  }

  /**
   * Get the names of the website fields.
   *
   * @throws \CRM_Core_Exception
   */
  public function getFieldWebsiteTypes() {
    return CRM_Utils_Array::collect('im_provider_id', $this->getMappingFields());
  }

  /**
   * Get an instance of the importer object.
   *
   * @return CRM_Contact_Import_Parser_Contact
   *
   * @throws \CRM_Core_Exception
   */
  public function getImporterObject() {
    $importer = new CRM_Contact_Import_Parser_Contact($this->getFieldNames());
    $importer->setUserJobID($this->getUserJobID());
    $importer->init();
    return $importer;
  }

  /**
   * Load the mapping from the datbase into the format that would be received from the UI.
   *
   * @throws \CRM_Core_Exception
   */
  protected function loadSavedMapping() {
    $fields = civicrm_api3('MappingField', 'get', [
      'mapping_id' => $this->getMappingID(),
      'options' => ['limit' => 0],
    ])['values'];
    $skipped = [];
    foreach ($fields as $index => $field) {
      if (!$this->isValidField($field['name'])) {
        // This scenario could occur if the name of a saved mapping field
        // changed or became unavailable https://lab.civicrm.org/dev/core/-/issues/3511.
        $skipped[] = $field['name'];
        $fields[$index]['name'] = $field['name'] = 'do_not_import';
      }
      $fieldSpec = $this->getFieldMetadata($field['name']);
      $fields[$index]['label'] = $fieldSpec['title'];
      if (empty($field['location_type_id']) && !empty($fieldSpec['hasLocationType'])) {
        $fields[$index]['location_type_id'] = 'Primary';
      }
    }
    if (!empty($skipped)) {
      CRM_Core_Session::setStatus(ts('Invalid saved mappings were skipped') . ':' . implode(', ', $skipped));
    }
    $this->mappingFields = $this->rekeyBySortedColumnNumbers($fields);
  }

  /**
   * Get the metadata for the field.
   *
   * @param string $fieldName
   *
   * @return array
   */
  protected function getFieldMetadata(string $fieldName): array {
    return $this->getMetadata()[$fieldName] ?? CRM_Contact_BAO_Contact::importableFields('All')[$fieldName];
  }

  /**
   * Is the field valid for this import.
   *
   * If not defined in metadata is is not valid.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  public function isValidField(string $fieldName): bool {
    return isset($this->getMetadata()[$fieldName]) || isset(CRM_Contact_BAO_Contact::importableFields('All')[$fieldName]);
  }

  /**
   * Load the mapping from the database into the pre-5.50 format.
   *
   * This is preserved as a copy the upgrade script can use - since the
   * upgrade allows the other to be 'fixed'.
   *
   * @throws \CRM_Core_Exception
   */
  protected function legacyLoadSavedMapping() {
    $fields = civicrm_api3('MappingField', 'get', [
      'mapping_id' => $this->getMappingID(),
      'options' => ['limit' => 0],
    ])['values'];
    foreach ($fields as $index => $field) {
      // Fix up the fact that for lost reasons we save by label not name.
      $fields[$index]['label'] = $field['name'];
      if (empty($field['relationship_type_id'])) {
        $fields[$index]['name'] = $this->getNameFromLabel($field['name']);
      }
      else {
        // Honour legacy chaos factor.
        if ($field['name'] === ts('- do not import -')) {
          // This is why we save names not labels people....
          $field['name'] = 'do_not_import';
        }
        $fields[$index]['name'] = strtolower(str_replace(" ", "_", $field['name']));
        // fix for edge cases, CRM-4954
        if ($fields[$index]['name'] === 'image_url') {
          $fields[$index]['name'] = str_replace('url', 'URL', $fields[$index]['name']);
        }
      }
      $fieldSpec = $this->getMetadata()[$fields[$index]['name']];
      if (empty($field['location_type_id']) && !empty($fieldSpec['hasLocationType'])) {
        $fields[$index]['location_type_id'] = 'Primary';
      }
    }
    $this->mappingFields = $this->rekeyBySortedColumnNumbers($fields);
  }

  /**
   * Get the titles from metadata.
   */
  public function getMetadataTitles() {
    if (empty($this->metadataByTitle)) {
      $this->metadataByTitle = CRM_Utils_Array::collect('title', $this->getMetadata());
    }
    return $this->metadataByTitle;
  }

  /**
   * Rekey the array by the column_number.
   *
   * @param array $mappingFields
   *
   * @return array
   */
  protected function rekeyBySortedColumnNumbers(array $mappingFields) {
    $this->mappingFields = CRM_Utils_Array::rekey($mappingFields, 'column_number');
    ksort($this->mappingFields);
    return $this->mappingFields;
  }

  /**
   * Get the field name from the label.
   *
   * @param string $label
   *
   * @return string
   */
  protected function getNameFromLabel($label) {
    $titleMap = array_flip($this->getMetadataTitles());
    $label = str_replace(' (match to contact)', '', $label);
    return $titleMap[$label] ?? '';
  }

  /**
   * Validate the key against the relationships available for the contatct type & subtype.
   *
   * @param string $key
   *
   * @return bool
   */
  protected function isValidRelationshipKey($key) {
    return !empty($this->getValidRelationships()[$key]);
  }

  /**
   * Get the defaults for the column from the saved mapping.
   *
   * @param int $column
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getSavedQuickformDefaultsForColumn($column) {
    $fieldMapping = [];

    if ($this->getValidRelationshipKey($column)) {
      $fieldMapping[] = $this->getValidRelationshipKey($column);
    }

    // $sel1
    $fieldMapping[] = $this->getFieldName($column);

    // $sel2
    if ($this->getWebsiteTypeID($column)) {
      $fieldMapping[] = $this->getWebsiteTypeID($column);
    }
    elseif ($this->getLocationTypeID($column)) {
      $fieldMapping[] = $this->getLocationTypeID($column);
    }

    // $sel3
    if ($this->getPhoneOrIMTypeID($column)) {
      $fieldMapping[] = $this->getPhoneOrIMTypeID($column);
    }
    return $fieldMapping;
  }

  /**
   * This exists for use in the FiveFifty Upgrade
   *
   * @throws \CRM_Core_Exception
   */
  public static function convertSavedFields(): void {
    $mappings = Mapping::get(FALSE)
      ->setSelect(['id', 'contact_type'])
      ->addWhere('mapping_type_id:name', '=', 'Import Contact')
      ->execute();

    foreach ($mappings as $mapping) {
      $processor = new CRM_Import_ImportProcessor();
      $processor->setMappingID($mapping['id']);
      $processor->setMetadata(CRM_Contact_BAO_Contact::importableFields('All'));
      $processor->legacyLoadSavedMapping();
      foreach ($processor->getMappingFields() as $field) {
        // The if is mostly precautionary against running this more than once
        // - which is common in dev if not live...
        if ($field['name']) {
          MappingField::update(FALSE)
            ->setValues(['name' => $field['name']])
            ->addWhere('id', '=', $field['id'])
            ->execute();
        }
      }
    }
  }

}
