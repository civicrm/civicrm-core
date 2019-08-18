<?php

/**
 * Trait CRM_Contact_Import_MetadataTrait
 *
 * Trait for handling contact import specific metadata so it
 * does not need to be passed from one form to the next.
 */
trait CRM_Contact_Import_MetadataTrait {

  /**
   * Get metadata for contact importable fields.
   *
   * @return array
   */
  protected function getContactImportMetadata(): array {
    $cacheKey = 'importable_contact_field_metadata' . $this->getContactType() . $this->getContactSubType();
    if (Civi::cache('fields')->has($cacheKey)) {
      return Civi::cache('fields')->get($cacheKey);
    }
    $contactFields = CRM_Contact_BAO_Contact::importableFields($this->getContactType());
    // exclude the address options disabled in the Address Settings
    $fields = CRM_Core_BAO_Address::validateAddressOptions($contactFields);

    //CRM-5125
    //supporting import for contact subtypes
    $csType = NULL;
    if ($this->getContactSubType()) {
      //custom fields for sub type
      $subTypeFields = CRM_Core_BAO_CustomField::getFieldsForImport($this->getContactSubType());

      if (!empty($subTypeFields)) {
        foreach ($subTypeFields as $customSubTypeField => $details) {
          $fields[$customSubTypeField] = $details;
        }
      }
    }

    foreach ($this->getRelationships() as $key => $var) {
      list($type) = explode('_', $key);
      $relationshipType[$key]['title'] = $var;
      $relationshipType[$key]['headerPattern'] = '/' . preg_quote($var, '/') . '/';
      $relationshipType[$key]['import'] = TRUE;
      $relationshipType[$key]['relationship_type_id'] = $type;
      $relationshipType[$key]['related'] = TRUE;
    }

    if (!empty($relationshipType)) {
      $fields = array_merge($fields, [
        'related' => [
          'title' => ts('- related contact info -'),
        ],
      ], $relationshipType);
    }
    Civi::cache('fields')->set($cacheKey, $fields);
    return $fields;
  }

  /**
   * Get sorted available relationships.
   *
   * @return array
   */
  protected function getRelationships(): array {
    $cacheKey = 'importable_contact_relationship_field_metadata' . $this->getContactType() . $this->getContactSubType();
    if (Civi::cache('fields')->has($cacheKey)) {
      return Civi::cache('fields')->get($cacheKey);
    }
    //Relationship importables
    $relations = CRM_Contact_BAO_Relationship::getContactRelationshipType(
      NULL, NULL, NULL, $this->getContactType(),
      FALSE, 'label', TRUE, $this->getContactSubType()
    );
    asort($relations);
    Civi::cache('fields')->set($cacheKey, $relations);
    return $relations;
  }

  /**
   * Get an array of header patterns for importable keys.
   *
   * @return array
   */
  public function getHeaderPatterns() {
    return CRM_Utils_Array::collect('headerPattern', $this->getContactImportMetadata());
  }

  /**
   * Get an array of header patterns for importable keys.
   *
   * @return array
   */
  public function getDataPatterns() {
    return CRM_Utils_Array::collect('dataPattern', $this->getContactImportMetadata());
  }

  /**
   * Get an array of header patterns for importable keys.
   *
   * @return array
   */
  public function getFieldTitles() {
    return CRM_Utils_Array::collect('title', $this->getContactImportMetadata());
  }

  /**
   * Get configured contact type.
   */
  protected function getContactType() {
    return $this->_contactType ?? 'Individual';
  }

  /**
   * Get configured contact sub type.
   *
   * @return string
   */
  protected function getContactSubType() {
    return $this->_contactSubType ?? NULL;
  }

}
