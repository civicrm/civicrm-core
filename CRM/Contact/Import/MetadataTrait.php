<?php

/**
 * Trait CRM_Contact_Import_MetadataTrait
 *
 * Trait for handling contact import specific metadata so it
 * does not need to be passed from one form to the next.
 *
 * @deprecated since 5.69 will be removed around 5.75
 */
trait CRM_Contact_Import_MetadataTrait {

  /**
   * Get metadata for contact importable fields.
   *
   * @deprecated since 5.69 will be removed around 5.75
   *
   * @return array
   */
  protected function getContactImportMetadata(): array {
    CRM_Core_Error::deprecatedWarning('use apiv4');
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
      [$type] = explode('_', $key);
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
   *
   * @deprecated since 5.69 will be removed around 5.75
   */
  protected function getRelationships(): array {
    CRM_Core_Error::deprecatedWarning('use apiv4');
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
   * We should do this work on the form layer.
   *
   * @deprecated will be removed around 5.75
   * @return array
   */
  public function getHeaderPatterns(): array {
    CRM_Core_Error::deprecatedWarning('use apiv4');
    return CRM_Utils_Array::collect('headerPattern', $this->getContactImportMetadata());
  }

  /**
   * Get an array of header patterns for importable keys.
   *
   * @deprecated since 5.69 will be removed around 5.75
   *
   * @return array
   */
  public function getFieldTitles() {
    CRM_Core_Error::deprecatedWarning('use apiv4');
    return CRM_Utils_Array::collect('title', $this->getContactImportMetadata());
  }

}
