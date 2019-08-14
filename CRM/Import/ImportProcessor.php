<?php

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
    return $this->contactSubType;
  }

  /**
   * Set contact subtype for import.
   *
   * @param string $contactSubType
   */
  public function setContactSubType(string $contactSubType) {
    $this->contactSubType = $contactSubType;
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
   * @throws \CiviCRM_API3_Exception
   */
  public function setMetadata(array $metadata) {
    $fieldDetails = civicrm_api3('CustomField', 'get', [
      'return' => ['custom_group_id.title'],
      'options' => ['limit' => 0],
    ])['values'];
    foreach ($metadata as $index => $field) {
      if (!empty($field['custom_field_id'])) {
        // The 'label' format for import is custom group title :: custom name title
        $metadata[$index]['name'] = $index;
        $metadata[$index]['title'] .= ' :: ' . $fieldDetails[$field['custom_field_id']]['custom_group_id.title'];
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
   * @param int $contactTypeKey
   */
  public function setContactTypeByConstant($contactTypeKey) {
    $constantTypeMap = [
      CRM_Import_Parser::CONTACT_INDIVIDUAL => 'Individual',
      CRM_Import_Parser::CONTACT_HOUSEHOLD => 'Household',
      CRM_Import_Parser::CONTACT_ORGANIZATION => 'Organization',
    ];
    $this->contactType = $constantTypeMap[$contactTypeKey];
  }

  /**
   * Get Mapping Fields.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function getMappingFields(): array {
    if (empty($this->mappingFields) && !empty($this->getMappingID())) {
      $this->loadSavedMapping();
    }
    return $this->mappingFields;
  }

  /**
   * @param array $mappingFields
   */
  public function setMappingFields(array $mappingFields) {
    $this->mappingFields = $this->rekeyBySortedColumnNumbers($mappingFields);
  }

  /**
   * Get the names of the mapped fields.
   *
   * @throws \CiviCRM_API3_Exception
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
   * @throws \CiviCRM_API3_Exception
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
   * @throws \CiviCRM_API3_Exception
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
   * @throws \CiviCRM_API3_Exception
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
   * @throws \CiviCRM_API3_Exception
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
   * @throws \CiviCRM_API3_Exception
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
   * @throws \CiviCRM_API3_Exception
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
   * @throws \CiviCRM_API3_Exception
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
   * @throws \CiviCRM_API3_Exception
   */
  public function getPhoneOrIMTypeID($columnNumber) {
    return $this->getIMProviderID($columnNumber) ?? $this->getPhoneTypeID($columnNumber);
  }

  /**
   * Get the location types of the mapped fields.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function getFieldLocationTypes() {
    return CRM_Utils_Array::collect('location_type_id', $this->getMappingFields());
  }

  /**
   * Get the phone types of the mapped fields.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function getFieldPhoneTypes() {
    return CRM_Utils_Array::collect('phone_type_id', $this->getMappingFields());
  }

  /**
   * Get the names of the im_provider fields.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function getFieldIMProviderTypes() {
    return CRM_Utils_Array::collect('im_provider_id', $this->getMappingFields());
  }

  /**
   * Get the names of the website fields.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function getFieldWebsiteTypes() {
    return CRM_Utils_Array::collect('im_provider_id', $this->getMappingFields());
  }

  /**
   * Get an instance of the importer object.
   *
   * @return CRM_Contact_Import_Parser_Contact
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function getImporterObject() {
    $importer = new CRM_Contact_Import_Parser_Contact(
      $this->getFieldNames(),
      $this->getFieldLocationTypes(),
      $this->getFieldPhoneTypes(),
      $this->getFieldIMProviderTypes(),
      // @todo - figure out related mappings.
      // $mapperRelated = [], $mapperRelatedContactType = [], $mapperRelatedContactDetails = [], $mapperRelatedContactLocType = [], $mapperRelatedContactPhoneType = [], $mapperRelatedContactImProvider = [],
      [],
      [],
      [],
      [],
      [],
      [],
      $this->getFieldWebsiteTypes()
      // $mapperRelatedContactWebsiteType = []
    );
    $importer->init();
    $importer->_contactType = $this->getContactType();
    return $importer;
  }

  /**
   * Load the mapping from the datbase into the format that would be received from the UI.
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function loadSavedMapping() {
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
    return array_values($this->mappingFields);
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
    return !empty($this->getValidRelationships()[$key]) ? TRUE : FALSE;
  }

  /**
   * Get the relevant js for quickform.
   *
   * @param int $column
   *
   * @return string
   * @throws \CiviCRM_API3_Exception
   */
  public function getQuickFormJSForField($column) {
    if ($this->getValidRelationshipKey($column)
      && !$this->getWebsiteTypeID($column)
      && !$this->getLocationTypeID($column)
    ) {
      return $this->getFormName() . "['mapper[$column][2]'].style.display = 'none';\n";
    }
    return '';
  }

}
