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

/**
 * This class contains function for UFField.
 */
class CRM_Core_BAO_UFField extends CRM_Core_DAO_UFField implements \Civi\Core\HookInterface {
  /**
   * Batch entry fields.
   * @var array
   */
  private static $_contriBatchEntryFields = NULL;
  private static $_memberBatchEntryFields = NULL;

  /**
   * Create UFField object.
   *
   * @param array $params
   *   Array per getfields metadata.
   *
   * @return \CRM_Core_BAO_UFField
   * @throws \CRM_Core_Exception
   */
  public static function create($params) {
    $id = $params['id'] ?? NULL;

    $op = empty($id) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($op, 'UFField', $id, $params);
    // Merge in data from existing field
    if (!empty($id)) {
      $UFField = new CRM_Core_BAO_UFField();
      $UFField->id = $params['id'];
      if ($UFField->find(TRUE)) {
        $defaults = $UFField->toArray();
        // This will be calculated based on field name
        unset($defaults['field_type']);
        $params += $defaults;
      }
      else {
        throw new CRM_Core_Exception("UFFIeld id {$params['id']} not found.");
      }
    }

    // Validate field_name
    if (strpos($params['field_name'], 'formatting') !== 0 && !CRM_Core_BAO_UFField::isValidFieldName($params['field_name'])) {
      throw new CRM_Core_Exception('The field_name is not valid');
    }

    // Supply default label if not set
    if (empty($id) && !isset($params['label'])) {
      $params['label'] = self::getAvailableFieldTitles()[$params['field_name']];
    }

    // Supply field_type if not set
    if (empty($params['field_type']) && strpos($params['field_name'], 'formatting') !== 0) {
      $params['field_type'] = CRM_Utils_Array::pathGet(self::getAvailableFieldsFlat(), [$params['field_name'], 'field_type']);
    }
    elseif (empty($params['field_type'])) {
      $params['field_type'] = 'Formatting';
    }

    // Generate unique name for formatting fields
    if ($params['field_name'] === 'formatting') {
      $params['field_name'] = 'formatting_' . substr(uniqid(), -4);
    }

    if (self::duplicateField($params)) {
      throw new CRM_Core_Exception("The field was not added. It already exists in this profile.");
    }

    //@todo why is this even optional? Surely weight should just be 'managed' ??
    if (CRM_Utils_Array::value('option.autoweight', $params, TRUE)) {
      $params['weight'] = CRM_Core_BAO_UFField::autoWeight($params);
    }

    // Set values for uf field properties and save
    $ufField = new CRM_Core_DAO_UFField();
    $ufField->copyValues($params);

    if ($params['field_name'] == 'url') {
      $ufField->location_type_id = 'null';
    }
    else {
      $ufField->website_type_id = 'null';
    }
    if (!strstr($params['field_name'], 'phone')) {
      $ufField->phone_type_id = 'null';
    }

    $ufField->save();

    $fieldsType = CRM_Core_BAO_UFGroup::calculateGroupType($ufField->uf_group_id, TRUE);
    CRM_Core_BAO_UFGroup::updateGroupTypes($ufField->uf_group_id, $fieldsType);

    CRM_Utils_Hook::post($op, 'UFField', $ufField->id, $ufField);

    civicrm_api3('profile', 'getfields', ['cache_clear' => TRUE]);
    return $ufField;
  }

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    //check if custom data profile field is disabled
    if ($is_active) {
      if (CRM_Core_BAO_UFField::checkUFStatus($id)) {
        return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_UFField', $id, 'is_active', $is_active);
      }
      else {
        CRM_Core_Session::setStatus(ts('Cannot enable this UF field since the used custom field is disabled.'), ts('Check Custom Field'), 'error');
      }
    }
    else {
      return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_UFField', $id, 'is_active', $is_active);
    }
  }

  /**
   * Delete the profile Field.
   *
   * @param int $id
   * @deprecated
   * @return bool
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    return (bool) self::deleteRecord(['id' => $id]);
  }

  /**
   * Check duplicate for duplicate field in a group.
   *
   * @param array $params
   *   An associative array with field and values.
   *
   * @return bool
   */
  public static function duplicateField($params) {
    $ufField = new CRM_Core_DAO_UFField();
    $ufField->uf_group_id = $params['uf_group_id'] ?? NULL;
    $ufField->field_type = $params['field_type'] ?? NULL;
    $ufField->field_name = $params['field_name'] ?? NULL;
    $ufField->website_type_id = $params['website_type_id'] ?? NULL;
    if (array_key_exists('location_type_id', $params) && is_null($params['location_type_id'])) {
      // primary location type have NULL value in DB
      $ufField->whereAdd("location_type_id IS NULL");
    }
    else {
      $ufField->location_type_id = $params['location_type_id'] ?? NULL;
    }
    $ufField->phone_type_id = $params['phone_type_id'] ?? NULL;

    if (!empty($params['id'])) {
      $ufField->whereAdd("id <> " . $params['id']);
    }

    return (bool) $ufField->find(TRUE);
  }

  /**
   * Returns the id of the first multi-record custom group in this profile (if any).
   *
   * @param int $gId
   *
   * @return int|false
   */
  public static function checkMultiRecordFieldExists($gId) {
    $queryString = "SELECT f.field_name
                        FROM   civicrm_uf_field f, civicrm_uf_group g
                        WHERE  f.uf_group_id = g.id
                          AND  g.id = %1 AND f.field_name LIKE 'custom%'";
    $p = [1 => [$gId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($queryString, $p);

    while ($dao->fetch()) {
      $customId = CRM_Core_BAO_CustomField::getKeyID($dao->field_name);
      if ($customId && is_numeric($customId)) {
        $multiRecordGroupId = CRM_Core_BAO_CustomField::isMultiRecordField($customId);
        if ($multiRecordGroupId) {
          return $multiRecordGroupId;
        }
      }
    }

    return FALSE;
  }

  /**
   * Automatically determine one weight and modify others.
   *
   * @param array $params
   *   UFField record, e.g. with 'weight', 'uf_group_id', and 'field_id'.
   * @return int
   */
  public static function autoWeight($params) {
    // fix for CRM-316
    $oldWeight = NULL;

    if (!empty($params['field_id']) || !empty($params['id'])) {
      $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFField', !empty($params['id']) ? $params['id'] : $params['field_id'], 'weight', 'id');
    }
    $fieldValues = ['uf_group_id' => !empty($params['uf_group_id']) ? $params['uf_group_id'] : $params['group_id']];
    return CRM_Utils_Weight::updateOtherWeights('CRM_Core_DAO_UFField', $oldWeight, $params['weight'] ?? 0, $fieldValues);
  }

  /**
   * Enable/disable profile field given a custom field id
   *
   * @param int $customFieldId
   *   Custom field id.
   * @param bool $is_active
   *   Set the is_active field.
   */
  public static function setUFField($customFieldId, $is_active) {
    // Find the profile id given custom field.
    $ufField = new CRM_Core_DAO_UFField();
    $ufField->field_name = "custom_" . $customFieldId;

    $ufField->find();
    while ($ufField->fetch()) {
      // Enable/ disable profile.
      CRM_Core_BAO_UFField::setIsActive($ufField->id, $is_active);
    }
  }

  /**
   * Copy existing profile fields to
   * new profile from the already built profile
   *
   * @deprecated
   *
   * @param int $old_id
   *   From which we need to copy.
   * @param bool $new_id
   *   In which to copy.
   */
  public static function copy($old_id, $new_id) {
    CRM_Core_Error::deprecatedFunctionWarning('');
    $ufField = new CRM_Core_DAO_UFField();
    $ufField->uf_group_id = $old_id;
    $ufField->find();
    while ($ufField->fetch()) {
      //copy the field records as it is on new ufgroup id
      $ufField->uf_group_id = $new_id;
      $ufField->id = NULL;
      $ufField->save();
    }
  }

  /**
   * Delete profile field given a custom field.
   *
   * @param int $customFieldId
   * @deprecated
   */
  public static function delUFField($customFieldId) {
    CRM_Core_Error::deprecatedFunctionWarning('Api');
    //find the profile id given custom field id
    $ufField = new CRM_Core_DAO_UFField();
    $ufField->field_name = "custom_" . $customFieldId;

    $ufField->find();
    while ($ufField->fetch()) {
      //enable/ disable profile
      CRM_Core_BAO_UFField::deleteRecord(['id' => $ufField->id]);
    }
  }

  /**
   * Enable/disable profile field given a custom group id
   *
   * @param int $customGroupId
   *   Custom group id.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   */
  public static function setUFFieldStatus($customGroupId, $is_active) {
    //find the profile id given custom group id
    $queryString = "SELECT civicrm_custom_field.id as custom_field_id
                        FROM   civicrm_custom_field, civicrm_custom_group
                        WHERE  civicrm_custom_field.custom_group_id = civicrm_custom_group.id
                          AND  civicrm_custom_group.id = %1";
    $p = [1 => [$customGroupId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($queryString, $p);

    while ($dao->fetch()) {
      // Enable/ disable profile.
      CRM_Core_BAO_UFField::setUFField($dao->custom_field_id, $is_active);
    }
  }

  /**
   * Check the status of custom field used in uf fields.
   *
   * @param int $UFFieldId
   *
   * @return bool
   *   false if custom field are disabled else true
   */
  public static function checkUFStatus($UFFieldId) {
    $fieldName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFField', $UFFieldId, 'field_name');
    // return if field is not a custom field
    if (!$customFieldId = CRM_Core_BAO_CustomField::getKeyID($fieldName)) {
      return TRUE;
    }

    $customField = new CRM_Core_DAO_CustomField();
    $customField->id = $customFieldId;
    // if uf field is custom field
    if ($customField->find(TRUE)) {
      if (!$customField->is_active) {
        return FALSE;
      }
      else {
        return TRUE;
      }
    }
  }

  /**
   * Find out whether given profile group using Activity
   * Profile fields with contact fields
   *
   * @param int $ufGroupId
   *
   * @return bool
   */
  public static function checkContactActivityProfileType($ufGroupId) {
    $ufGroup = new CRM_Core_DAO_UFGroup();
    $ufGroup->id = $ufGroupId;
    $ufGroup->find(TRUE);

    return self::checkContactActivityProfileTypeByGroupType($ufGroup->group_type);
  }

  /**
   * FIXME say 10 ha
   * @param $ufGroupType
   * @return bool
   */
  public static function checkContactActivityProfileTypeByGroupType($ufGroupType) {
    $profileTypes = [];
    if ($ufGroupType) {
      $typeParts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $ufGroupType);
      $profileTypes = explode(',', $typeParts[0]);
    }

    if (empty($profileTypes)) {
      return FALSE;
    }
    $components = ['Contribution', 'Participant', 'Membership'];
    if (!in_array('Activity', $profileTypes)) {
      return FALSE;
    }
    elseif (count($profileTypes) == 1) {
      return FALSE;
    }

    if ($index = array_search('Contact', $profileTypes)) {
      unset($profileTypes[$index]);
      if (count($profileTypes) == 1) {
        return TRUE;
      }
    }

    $contactTypes = CRM_Contact_BAO_ContactType::basicTypes(TRUE);
    $subTypes = CRM_Contact_BAO_ContactType::subTypes();

    $profileTypeComponent = array_intersect($components, $profileTypes);
    if (!empty($profileTypeComponent) ||
      count(array_intersect($contactTypes, $profileTypes)) > 1 ||
      count(array_intersect($subTypes, $profileTypes)) > 1
    ) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Find out whether given profile group uses $required
   * and/or $optional profile types
   *
   * @param int $ufGroupId
   *   Profile id.
   * @param array $required
   *   Array of types those are required.
   * @param array $optional
   *   Array of types those are optional.
   *
   * @return bool
   */
  public static function checkValidProfileType($ufGroupId, $required, $optional = NULL) {
    if (!is_array($required) || empty($required)) {
      return FALSE;
    }

    $ufGroup = new CRM_Core_DAO_UFGroup();
    $ufGroup->id = $ufGroupId;
    $ufGroup->find(TRUE);

    $profileTypes = [];
    if ($ufGroup->group_type) {
      $typeParts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $ufGroup->group_type);
      $profileTypes = explode(',', $typeParts[0]);
    }

    if (empty($profileTypes)) {
      return FALSE;
    }

    $valid = TRUE;
    foreach ($required as $key => $val) {
      if (!in_array($val, $profileTypes)) {
        $valid = FALSE;
        break;
      }
    }

    if ($valid && is_array($optional)) {
      foreach ($optional as $key => $val) {
        if (in_array($val, $profileTypes)) {
          $valid = TRUE;
          break;
        }
      }
    }

    return $valid;
  }

  /**
   * Check for mix profile fields (eg: individual + other contact types)
   *
   * @param int $ufGroupId
   *
   * @return bool
   *   true for mix profile else false
   */
  public static function checkProfileType($ufGroupId) {
    $ufGroup = new CRM_Core_DAO_UFGroup();
    $ufGroup->id = $ufGroupId;
    $ufGroup->find(TRUE);

    $profileTypes = [];
    if ($ufGroup->group_type) {
      $typeParts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $ufGroup->group_type);
      $profileTypes = explode(',', $typeParts[0]);
    }

    //early return if new profile.
    if (empty($profileTypes)) {
      return FALSE;
    }

    //we need to unset Contact
    if (count($profileTypes) > 1) {
      $index = array_search('Contact', $profileTypes);
      if ($index !== FALSE) {
        unset($profileTypes[$index]);
      }
    }

    // suppress any subtypes if present
    CRM_Contact_BAO_ContactType::suppressSubTypes($profileTypes);

    $contactTypes = array_merge(['Contact'], CRM_Contact_BAO_ContactType::basicTypes(TRUE));
    $components = ['Contribution', 'Participant', 'Membership', 'Activity'];

    // check for mix profile condition
    if (count($profileTypes) > 1) {
      //check the there are any components include in profile
      foreach ($components as $value) {
        if (in_array($value, $profileTypes)) {
          return TRUE;
        }
      }
      //check if there are more than one contact types included in profile
      if (count($profileTypes) > 1) {
        return TRUE;
      }
    }
    elseif (count($profileTypes) == 1) {
      // note for subtype case count would be zero
      $profileTypes = array_values($profileTypes);
      if (!in_array($profileTypes[0], $contactTypes)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get the profile type (eg: individual/organization/household)
   *
   * @param int $ufGroupId
   *   Uf group id.
   * @param bool $returnMixType
   *   This is true, then field type of mix profile field is returned.
   * @param bool $onlyPure
   *   True if only pure profiles are required.
   *
   * @param bool $skipComponentType
   *
   * @return string
   *   profile group_type
   *
   */
  public static function getProfileType($ufGroupId, $returnMixType = TRUE, $onlyPure = FALSE, $skipComponentType = FALSE) {
    $ufGroup = new CRM_Core_DAO_UFGroup();
    $ufGroup->id = $ufGroupId;
    $ufGroup->is_active = 1;

    $ufGroup->find(TRUE);
    return self::calculateProfileType($ufGroup->group_type, $returnMixType, $onlyPure, $skipComponentType);
  }

  /**
   * Get the profile type (eg: individual/organization/household)
   *
   * @param string $ufGroupType
   * @param bool $returnMixType
   *   This is true, then field type of mix profile field is returned.
   * @param bool $onlyPure
   *   True if only pure profiles are required.
   * @param bool $skipComponentType
   *
   * @return string  profile group_type
   *
   */
  public static function calculateProfileType($ufGroupType, $returnMixType = TRUE, $onlyPure = FALSE, $skipComponentType = FALSE) {
    // profile types
    $contactTypes = array_merge(['Contact'], CRM_Contact_BAO_ContactType::basicTypes(TRUE));
    $subTypes = CRM_Contact_BAO_ContactType::subTypes();
    $components = ['Contribution', 'Participant', 'Membership', 'Activity'];

    $profileTypes = [];
    if ($ufGroupType) {
      $typeParts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $ufGroupType);
      $profileTypes = explode(',', $typeParts[0]);
    }

    if ($onlyPure) {
      if (count($profileTypes) == 1) {
        return $profileTypes[0];
      }
      else {
        return NULL;
      }
    }

    //we need to unset Contact
    if (count($profileTypes) > 1) {
      $index = array_search('Contact', $profileTypes);
      if ($index !== FALSE) {
        unset($profileTypes[$index]);
      }
    }

    $profileType = $mixProfileType = NULL;

    // this case handles pure profile
    if (count($profileTypes) == 1) {
      $profileType = array_pop($profileTypes);
    }
    else {
      //check the there are any components include in profile
      $componentCount = [];
      foreach ($components as $value) {
        if (in_array($value, $profileTypes)) {
          $componentCount[] = $value;
        }
      }

      //check contact type included in profile
      $contactTypeCount = [];
      foreach ($contactTypes as $value) {
        if (in_array($value, $profileTypes)) {
          $contactTypeCount[] = $value;
        }
      }
      // subtype counter
      $subTypeCount = [];
      foreach ($subTypes as $value) {
        if (in_array($value, $profileTypes)) {
          $subTypeCount[] = $value;
        }
      }
      if (!$skipComponentType && count($componentCount) == 1) {
        $profileType = $componentCount[0];
      }
      elseif (count($componentCount) > 1) {
        $mixProfileType = $componentCount[1];
      }
      elseif (count($subTypeCount) == 1) {
        $profileType = $subTypeCount[0];
      }
      elseif (count($contactTypeCount) == 1) {
        $profileType = $contactTypeCount[0];
      }
      elseif (count($subTypeCount) > 1) {
        // this is mix subtype profiles
        $mixProfileType = $subTypeCount[1];
      }
      elseif (count($contactTypeCount) > 1) {
        // this is mix contact profiles
        $mixProfileType = $contactTypeCount[1];
      }
    }

    if ($mixProfileType) {
      if ($returnMixType) {
        return $mixProfileType;
      }
      else {
        return 'Mixed';
      }
    }
    else {
      return $profileType;
    }
  }

  /**
   * Check for searchable or in selector field for given profile.
   *
   * @param int $profileID
   *
   * @return bool
   */
  public static function checkSearchableORInSelector($profileID) {
    $result = FALSE;
    if (!$profileID) {
      return $result;
    }

    $query = "
SELECT  id
  From  civicrm_uf_field
 WHERE  (in_selector = 1 OR is_searchable = 1)
   AND  uf_group_id = {$profileID}";

    $ufFields = CRM_Core_DAO::executeQuery($query);
    while ($ufFields->fetch()) {
      $result = TRUE;
      break;
    }

    return $result;
  }

  /**
   * Reset In selector and is searchable values for given $profileID.
   *
   * @param int $profileID
   */
  public static function resetInSelectorANDSearchable($profileID) {
    if (!$profileID) {
      return;
    }
    $query = "UPDATE civicrm_uf_field SET in_selector = 0, is_searchable = 0 WHERE  uf_group_id = {$profileID}";
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Add fields to $profileAddressFields as appropriate.
   * profileAddressFields is assigned to the template to tell it
   * what fields are in the profile address
   * that potentially should be copied to the Billing fields
   * we want to give precedence to
   *   1) Billing &
   *   2) then Primary designated as 'Primary
   *   3) location_type is primary
   *   4) if none of these apply then it just uses the first one
   *
   *   as this will be used to
   * transfer profile address data to billing fields
   * http://issues.civicrm.org/jira/browse/CRM-5869
   *
   * @param string $key
   *   Field key - e.g. street_address-Primary, first_name.
   * @param array $profileAddressFields
   *   Array of profile fields that relate to address fields.
   * @param array $profileFilter
   *   Filter to apply to profile fields - expected usage is to only fill based on.
   *   the bottom profile per CRM-13726
   * @param array $paymentProcessorBillingFields
   *   Array of billing fields required by the payment processor.
   *
   * @return bool
   *   Can the address block be hidden safe in the knowledge all fields are elsewhere collected (see CRM-15118)
   */
  public static function assignAddressField($key, &$profileAddressFields, $profileFilter, $paymentProcessorBillingFields = NULL) {
    $billing_id = CRM_Core_BAO_LocationType::getBilling();
    [$prefixName, $index] = CRM_Utils_System::explode('-', $key, 2);

    $profileFields = civicrm_api3('uf_field', 'get', array_merge($profileFilter,
      [
        'is_active' => 1,
        'return' => 'field_name, is_required',
        'options' => [
          'limit' => 0,
        ],
      ]
    ));
    //check for valid fields ( fields that are present in billing block )
    if (!empty($paymentProcessorBillingFields)) {
      $validBillingFields = $paymentProcessorBillingFields;
    }
    else {
      $validBillingFields = [
        'first_name',
        'middle_name',
        'last_name',
        'street_address',
        'supplemental_address_1',
        'city',
        'state_province',
        'postal_code',
        'country',
      ];
    }
    $requiredBillingFields = array_diff($validBillingFields, ['middle_name', 'supplemental_address_1']);
    $validProfileFields = [];
    $requiredProfileFields = [];

    foreach ($profileFields['values'] as $field) {
      if (in_array($field['field_name'], $validBillingFields)) {
        $validProfileFields[] = $field['field_name'];
      }
      if (!empty($field['is_required'])) {
        $requiredProfileFields[] = $field['field_name'];
      }
    }

    if (!in_array($prefixName, $validProfileFields)) {
      return FALSE;
    }

    if (!empty($index) && (
        // it's empty so we set it OR
        empty($profileAddressFields[$prefixName])
        //we are dealing with billing id (precedence)
        || $index == $billing_id
        // we are dealing with primary & billing not set
        || ($index == 'Primary' && $profileAddressFields[$prefixName] != $billing_id)
        || ($index == CRM_Core_BAO_LocationType::getDefault()->id
          && $profileAddressFields[$prefixName] != $billing_id
          && $profileAddressFields[$prefixName] != 'Primary'
        )
      )
    ) {
      $profileAddressFields[$prefixName] = $index;
    }

    $potentiallyMissingRequiredFields = array_diff($requiredBillingFields, $requiredProfileFields);
    $billingProfileIsHideable = empty($potentiallyMissingRequiredFields);
    CRM_Core_Resources::singleton()
      ->addSetting(['billing' => ['billingProfileIsHideable' => $billingProfileIsHideable]]);
    return $billingProfileIsHideable;
  }

  /**
   * Get a list of fields which can be added to profiles.
   *
   * @param int $gid : UF group ID
   * @param array $defaults : Form defaults
   * @return array, multidimensional; e.g. $result['FieldGroup']['field_name']['label']
   */
  public static function getAvailableFields($gid = NULL, $defaults = []) {
    $fields = [
      'Contact' => [],
      'Individual' => CRM_Contact_BAO_Contact::importableFields('Individual', FALSE, FALSE, TRUE, TRUE, TRUE),
      'Household' => CRM_Contact_BAO_Contact::importableFields('Household', FALSE, FALSE, TRUE, TRUE, TRUE),
      'Organization' => CRM_Contact_BAO_Contact::importableFields('Organization', FALSE, FALSE, TRUE, TRUE, TRUE),
    ];

    // include hook injected fields
    $fields['Contact'] = array_merge($fields['Contact'], CRM_Contact_BAO_Query_Hook::singleton()->getFields());

    // add current employer for individuals
    $fields['Individual']['current_employer'] = [
      'name' => 'organization_name',
      'title' => ts('Current Employer'),
    ];

    $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options', TRUE, NULL, TRUE
    );

    if (empty($addressOptions['county'])) {
      unset($fields['Individual']['county'], $fields['Household']['county'], $fields['Organization']['county']);
    }

    // break out common contact fields array CRM-3037.
    // from a UI perspective this makes very little sense
    foreach ($fields['Individual'] as $key => $value) {
      if (!empty($fields['Household'][$key]) && !empty($fields['Organization'][$key])) {
        $fields['Contact'][$key] = $value;
        unset($fields['Individual'][$key], $fields['Household'][$key], $fields['Organization'][$key]);
      }
    }

    // Internal field not exposed to forms
    unset($fields['Contact']['contact_type']);
    unset($fields['Contact']['master_id']);

    // convert phone extension in to psedo-field phone + phone extension
    //unset extension
    unset($fields['Contact']['phone_ext']);
    //add psedo field
    $fields['Contact']['phone_and_ext'] = [
      'name' => 'phone_and_ext',
      'title' => ts('Phone and Extension'),
      'hasLocationType' => 1,
    ];

    // include Subtypes For Profile
    $subTypes = CRM_Contact_BAO_ContactType::subTypeInfo();
    foreach ($subTypes as $name => $val) {
      //custom fields for sub type
      $subTypeFields = CRM_Core_BAO_CustomField::getFieldsForImport($name, FALSE, FALSE, FALSE, TRUE, TRUE);
      if (array_key_exists($val['parent'], $fields)) {
        $fields[$name] = $fields[$val['parent']] + $subTypeFields;
      }
      else {
        $fields[$name] = $subTypeFields;
      }
    }

    if (CRM_Core_Permission::access('CiviContribute')) {
      $contribFields = CRM_Contribute_BAO_Contribution::getContributionFields(FALSE);
      if (!empty($contribFields)) {
        unset($contribFields['is_test']);
        unset($contribFields['is_pay_later']);
        unset($contribFields['contribution_id']);
        $contribFields['contribution_note'] = [
          'name' => 'contribution_note',
          'title' => ts('Contribution Note'),
        ];
        $fields['Contribution'] = array_merge($contribFields, self::getContribBatchEntryFields());
      }
    }

    if (CRM_Core_Permission::access('CiviEvent')) {
      $participantFields = CRM_Event_BAO_Query::getParticipantFields();
      if ($participantFields) {
        // Remove fields not supported by profiles
        CRM_Utils_Array::remove($participantFields,
          'external_identifier',
          'event_id',
          'participant_contact_id',
          'participant_role_id',
          'participant_status_id',
          'participant_is_test',
          'participant_fee_level',
          'participant_id',
          'participant_is_pay_later',
          'participant_campaign'
        );
        if (isset($participantFields['participant_campaign_id'])) {
          $participantFields['participant_campaign_id']['title'] = ts('Campaign');
        }
        $fields['Participant'] = $participantFields;
      }
    }

    if (CRM_Core_Permission::access('CiviMember')) {
      $membershipFields = CRM_Member_BAO_Membership::getMembershipFields();
      // Remove fields not supported by profiles
      CRM_Utils_Array::remove($membershipFields,
        'membership_id',
        'membership_type_id',
        'member_is_test',
        'is_override',
        'member_is_override',
        'status_override_end_date',
        'status_id',
        'member_is_pay_later'
      );
      if ($gid && CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $gid, 'name') == 'membership_batch_entry') {
        $fields['Membership'] = array_merge($membershipFields, self::getMemberBatchEntryFields());
      }
      else {
        $fields['Membership'] = $membershipFields;
      }
    }

    if (CRM_Core_Permission::access('CiviCase')) {
      $caseFields = CRM_Case_BAO_Query::getFields(TRUE);
      $caseFields = array_merge($caseFields, CRM_Core_BAO_CustomField::getFieldsForImport('Case'));
      if ($caseFields) {
        // Remove fields not supported by profiles
        CRM_Utils_Array::remove($caseFields,
          'case_id',
          'case_type',
          'case_role',
          'case_deleted'
        );
      }
      $fields['Case'] = $caseFields;
    }

    $activityFields = CRM_Activity_BAO_Activity::getProfileFields();
    if ($activityFields) {
      // campaign related fields.
      if (isset($activityFields['activity_campaign_id'])) {
        $activityFields['activity_campaign_id']['title'] = ts('Campaign');
      }
      $fields['Activity'] = $activityFields;
    }

    $fields['Formatting']['format_free_html_' . rand(1000, 9999)] = [
      'name' => 'free_html',
      'import' => FALSE,
      'export' => FALSE,
      'title' => 'Free HTML',
    ];

    // Sort by title
    foreach ($fields as &$values) {
      $values = CRM_Utils_Array::crmArraySortByField($values, 'title');
    }

    //group selected and unwanted fields list
    $ufFields = $gid ? CRM_Core_BAO_UFGroup::getFields($gid, FALSE, NULL, NULL, NULL, TRUE, NULL, TRUE) : [];
    $groupFieldList = array_merge($ufFields, [
      'note',
      'email_greeting_custom',
      'postal_greeting_custom',
      'addressee_custom',
      'id',
    ]);
    //unset selected fields
    foreach ($groupFieldList as $key => $value) {
      if (is_int($key)) {
        unset($fields['Individual'][$value], $fields['Household'][$value], $fields['Organization'][$value]);
        continue;
      }
      if (!empty($defaults['field_name'])
        && $defaults['field_name']['0'] == $value['field_type']
        && $defaults['field_name']['1'] == $key
      ) {
        continue;
      }
      unset($fields[$value['field_type']][$key]);
    }

    // Allow extensions to alter the array of entity => fields permissible in a CiviCRM Profile.
    CRM_Utils_Hook::alterUFFields($fields);
    return $fields;
  }

  /**
   * Get a list of fields which can be added to profiles.
   *
   * @param bool $force
   *
   * @return array
   *   e.g. $result['field_name']['label']
   */
  public static function getAvailableFieldsFlat($force = FALSE) {
    if (!isset(Civi::$statics['UFFieldsFlat']) || $force) {
      Civi::$statics['UFFieldsFlat'] = [];
      foreach (self::getAvailableFields() as $fieldType => $fields) {
        foreach ($fields as $fieldName => $field) {
          if (!isset(Civi::$statics['UFFieldsFlat'][$fieldName])) {
            $field['field_type'] = $fieldType;
            Civi::$statics['UFFieldsFlat'][$fieldName] = $field;
          }
        }
      }
    }
    return Civi::$statics['UFFieldsFlat'];
  }

  /**
   * Get a list of fields which can be added to profiles in the format [name => title]
   *
   * @return array
   */
  public static function getAvailableFieldTitles() {
    $fields = self::getAvailableFieldsFlat();
    $fields['formatting'] = ['title' => ts('Formatting')];
    return CRM_Utils_Array::collect('title', $fields);
  }

  /**
   * Get pseudoconstant list for `field_name`
   *
   * Includes APIv4-style names for custom fields for portability.
   *
   * @return array
   */
  public static function getAvailableFieldOptions() {
    $fields = self::getAvailableFieldsFlat();
    $fields['formatting'] = ['title' => ts('Formatting')];
    $options = [];
    foreach ($fields as $fieldName => $field) {
      $option = [
        'id' => $fieldName,
        'name' => $fieldName,
        'label' => $field['title'],
      ];
      if (!empty($field['custom_group_id']) && !empty($field['id'])) {
        $groupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $field['custom_group_id']);
        $fieldName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $field['id']);
        $option['name'] = "$groupName.$fieldName";
      }
      $options[] = $option;
    }
    return $options;
  }

  /**
   * Determine whether the given field_name is valid.
   *
   * @param string $fieldName
   * @return bool
   */
  public static function isValidFieldName($fieldName) {
    $availableFields = CRM_Core_BAO_UFField::getAvailableFieldsFlat();
    return isset($availableFields[$fieldName]);
  }

  /**
   * @return array|null
   */
  public static function getContribBatchEntryFields() {
    if (self::$_contriBatchEntryFields === NULL) {
      self::$_contriBatchEntryFields = [
        'send_receipt' => [
          'name' => 'send_receipt',
          'title' => ts('Send Receipt'),
        ],
        'soft_credit' => [
          'name' => 'soft_credit',
          'title' => ts('Soft Credit'),
        ],
        'soft_credit_type' => [
          'name' => 'soft_credit_type',
          'title' => ts('Soft Credit Type'),
        ],
        'product_name' => [
          'name' => 'product_name',
          'title' => ts('Premiums'),
        ],
        'contribution_note' => [
          'name' => 'contribution_note',
          'title' => ts('Contribution Note'),
        ],
        'contribution_soft_credit_pcp_id' => [
          'name' => 'contribution_soft_credit_pcp_id',
          'title' => ts('Personal Campaign Page'),
        ],
      ];
    }
    return self::$_contriBatchEntryFields;
  }

  /**
   * @return array|null
   */
  public static function getMemberBatchEntryFields() {
    if (self::$_memberBatchEntryFields === NULL) {
      self::$_memberBatchEntryFields = [
        'send_receipt' => [
          'name' => 'send_receipt',
          'title' => ts('Send Receipt'),
        ],
        'soft_credit' => [
          'name' => 'soft_credit',
          'title' => ts('Soft Credit'),
        ],
        'product_name' => [
          'name' => 'product_name',
          'title' => ts('Premiums'),
        ],
        'financial_type' => [
          'name' => 'financial_type',
          'title' => ts('Financial Type'),
        ],
        'total_amount' => [
          'name' => 'total_amount',
          'title' => ts('Total Amount'),
        ],
        'receive_date' => [
          'name' => 'receive_date',
          'title' => ts('Contribution Date'),
        ],
        'payment_instrument' => [
          'name' => 'payment_instrument',
          'title' => ts('Payment Method'),
        ],
        'contribution_status_id' => [
          'name' => 'contribution_status_id',
          'title' => ts('Contribution Status'),
        ],
        'trxn_id' => [
          'name' => 'contribution_trxn_id',
          'title' => ts('Contribution Transaction ID'),
        ],
      ];
    }
    return self::$_memberBatchEntryFields;
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function on_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if ($event->action === 'delete' && $event->entity === 'CustomField') {
      \Civi\Api4\UFField::delete(FALSE)
        ->addWhere('field_name', '=', 'custom_' . $event->id)
        ->execute();
    }
  }

}
