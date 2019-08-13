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
   * Get contact type being imported.
   *
   * @var string
   */
  protected $contactType;

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
   * @return array
   */
  public function getMappingFields(): array {
    return $this->mappingFields;
  }

  /**
   * @param array $mappingFields
   */
  public function setMappingFields(array $mappingFields) {
    $this->mappingFields = CRM_Utils_Array::rekey($mappingFields, 'column_number');
    ksort($this->mappingFields);
    $this->mappingFields = array_values($this->mappingFields);
  }

  /**
   * Get the names of the mapped fields.
   */
  public function getFieldNames() {
    return CRM_Utils_Array::collect('name', $this->getMappingFields());
  }

  /**
   * Get the location types of the mapped fields.
   */
  public function getFieldLocationTypes() {
    return CRM_Utils_Array::collect('location_type_id', $this->getMappingFields());
  }

  /**
   * Get the phone types of the mapped fields.
   */
  public function getFieldPhoneTypes() {
    return CRM_Utils_Array::collect('phone_type_id', $this->getMappingFields());
  }

  /**
   * Get the names of the im_provider fields.
   */
  public function getFieldIMProviderTypes() {
    return CRM_Utils_Array::collect('im_provider_id', $this->getMappingFields());
  }

  /**
   * Get the names of the website fields.
   */
  public function getFieldWebsiteTypes() {
    return CRM_Utils_Array::collect('im_provider_id', $this->getMappingFields());
  }

  /**
   * Get an instance of the importer object.
   *
   * @return CRM_Contact_Import_Parser_Contact
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

}
