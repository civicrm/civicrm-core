<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class contains function for UFField
 *
 */
class CRM_Core_BAO_UFField extends CRM_Core_DAO_UFField {

  /**
   * Batch entry fields
   */
  private static $_contriBatchEntryFields = NULL;
  private static $_memberBatchEntryFields = NULL;


  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Core_BAO_UFField object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_UFField', $params, $defaults);
  }

  /**
   * Get the form title.
   *
   * @param int $id id of uf_form
   *
   * @return string title
   *
   * @access public
   * @static
   *
   */
  public static function getTitle($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFField', $groupId, 'title');
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id         id of the database record
   * @param boolean  $is_active  value we want to set the is_active field
   *
   * @return Object              DAO object on sucess, null otherwise
   * @access public
   * @static
   */
  static function setIsActive($id, $is_active) {
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
   * @param int  $id    Field Id
   *
   * @return boolean
   *
   * @access public
   * @static
   *
   */
  public static function del($id) {
    //delete  field field
    $field = new CRM_Core_DAO_UFField();
    $field->id = $id;
    $field->delete();
    return TRUE;
  }

  /**
   * Function to check duplicate for duplicate field in a group
   *
   * @param array $params an associative array with field and values
   * @param $ids
   *
   * @return mixed
   * @ids   array $ids    array that containd ids
   *
   * @access public
   * @static
   */
  public static function duplicateField($params, $ids) {
    $ufField = new CRM_Core_DAO_UFField();
    $ufField->uf_group_id = CRM_Utils_Array::value('uf_group', $ids);
    $ufField->field_type = $params['field_name'][0];
    $ufField->field_name = $params['field_name'][1];
    if ($params['field_name'][1] == 'url') {
      $ufField->website_type_id = CRM_Utils_Array::value(2, $params['field_name'], NULL);
    }
    else {
      $ufField->location_type_id = (CRM_Utils_Array::value(2, $params['field_name'])) ? $params['field_name'][2] : 'NULL';
    }
    $ufField->phone_type_id = CRM_Utils_Array::value(3, $params['field_name']);

    if (!empty($ids['uf_field'])) {
      $ufField->whereAdd("id <> " . CRM_Utils_Array::value('uf_field', $ids));
    }

    return $ufField->find(TRUE);
  }

  /**
   * Does profile consists of a multi-record custom field
   */
  public static function checkMultiRecordFieldExists($gId) {
    $queryString = "SELECT f.field_name
                        FROM   civicrm_uf_field f, civicrm_uf_group g
                        WHERE  f.uf_group_id = g.id
                          AND  g.id = %1 AND f.field_name LIKE 'custom%'";
    $p = array(1 => array($gId, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($queryString, $p);
    $customFieldIds = array();
    $isMultiRecordFieldPresent = FALSE;
    while ($dao->fetch()) {
      if ($customId = CRM_Core_BAO_CustomField::getKeyID($dao->field_name)) {
        if (is_numeric($customId)) {
          $customFieldIds[] = $customId;
        }
      }
    }

    if (!empty($customFieldIds) && count($customFieldIds) == 1) {
      $customFieldId = array_pop($customFieldIds);
      $isMultiRecordFieldPresent = CRM_Core_BAO_CustomField::isMultiRecordField($customFieldId);
    }
    elseif (count($customFieldIds) > 1) {
      $customFieldIds = implode(", ", $customFieldIds);
      $queryString = "
      SELECT cg.id as cgId
 FROM civicrm_custom_group cg
 INNER JOIN civicrm_custom_field cf
 ON cg.id = cf.custom_group_id
WHERE cf.id IN (" . $customFieldIds . ") AND is_multiple = 1 LIMIT 0,1";

      $dao = CRM_Core_DAO::executeQuery($queryString);
      if ($dao->fetch()) {
        $isMultiRecordFieldPresent = ($dao->cgId) ? $dao->cgId : FALSE;
      }
    }

    return $isMultiRecordFieldPresent;
  }

  /**
   * function to add the UF Field
   *
   * @param array $params (reference) array containing the values submitted by the form
   * @param array $ids array containing the id
   *
   * @return object CRM_Core_BAO_UFField object
   *
   * @access public
   * @static
   *
   */
  static function add(&$params, $ids = array()) {
    // set values for uf field properties and save
    $ufField = new CRM_Core_DAO_UFField();
    $ufField->field_type = $params['field_name'][0];
    $ufField->field_name = $params['field_name'][1];

    //should not set location type id for Primary
    $locationTypeId = NULL;
    if ($params['field_name'][1] == 'url') {
      $ufField->website_type_id = CRM_Utils_Array::value(2, $params['field_name']);
    }
    else {
      $locationTypeId = CRM_Utils_Array::value(2, $params['field_name']);
      $ufField->website_type_id = NULL;
    }
    if ($locationTypeId) {
      $ufField->location_type_id = $locationTypeId;
    }
    else {
      $ufField->location_type_id = 'null';
    }

    $ufField->phone_type_id = CRM_Utils_Array::value(3, $params['field_name'], 'NULL');
    $ufField->listings_title = CRM_Utils_Array::value('listings_title', $params);
    $ufField->visibility = CRM_Utils_Array::value('visibility', $params);
    $ufField->help_pre = CRM_Utils_Array::value('help_pre', $params);
    $ufField->help_post = CRM_Utils_Array::value('help_post', $params);
    $ufField->label = CRM_Utils_Array::value('label', $params);
    $ufField->is_required = CRM_Utils_Array::value('is_required', $params, FALSE);
    $ufField->is_active = CRM_Utils_Array::value('is_active', $params, FALSE);
    $ufField->in_selector = CRM_Utils_Array::value('in_selector', $params, FALSE);
    $ufField->is_view = CRM_Utils_Array::value('is_view', $params, FALSE);
    $ufField->is_registration = CRM_Utils_Array::value('is_registration', $params, FALSE);
    $ufField->is_match = CRM_Utils_Array::value('is_match', $params, FALSE);
    $ufField->is_searchable = CRM_Utils_Array::value('is_searchable', $params, FALSE);
    $ufField->is_multi_summary = CRM_Utils_Array::value('is_multi_summary', $params, FALSE);
    $ufField->weight = CRM_Utils_Array::value('weight', $params, 0);

    // need the FKEY - uf group id
    $ufField->uf_group_id = CRM_Utils_Array::value('uf_group', $ids, FALSE);
    $ufField->id = CRM_Utils_Array::value('uf_field', $ids, FALSE);

    return $ufField->save();
  }

  /**
   * Automatically determine one weight and modify others
   *
   * @param array $params UFField record, e.g. with 'weight', 'uf_group_id', and 'field_id'
   * @return int
   */
  public static function autoWeight($params) {
    // fix for CRM-316
    $oldWeight = NULL;

    if (!empty($params['field_id'])) {
      $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFField', $params['field_id'], 'weight', 'id');
    }
    $fieldValues = array('uf_group_id' => $params['group_id']);
    return CRM_Utils_Weight::updateOtherWeights('CRM_Core_DAO_UFField', $oldWeight, CRM_Utils_Array::value('weight', $params, 0), $fieldValues);
  }

  /**
   * Function to enable/disable profile field given a custom field id
   *
   * @param int      $customFieldId     custom field id
   * @param boolean  $is_active         set the is_active field
   *
   * @return void
   * @static
   * @access public
   */
  static function setUFField($customFieldId, $is_active) {
    //find the profile id given custom field
    $ufField = new CRM_Core_DAO_UFField();
    $ufField->field_name = "custom_" . $customFieldId;

    $ufField->find();
    while ($ufField->fetch()) {
      //enable/ disable profile
      CRM_Core_BAO_UFField::setIsActive($ufField->id, $is_active);
    }
  }

  /**
   * Function to copy existing profile fields to
   * new profile from the already built profile
   *
   * @param int      $old_id  from which we need to copy
   * @param boolean  $new_id  in which to copy
   *
   * @return void
   * @static
   * @access public
   */
  static function copy($old_id, $new_id) {
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
   * Function to delete profile field given a custom field
   *
   * @param int   $customFieldId      ID of the custom field to be deleted
   *
   * @return void
   *
   * @static
   * @access public
   */
  static function delUFField($customFieldId) {
    //find the profile id given custom field id
    $ufField = new CRM_Core_DAO_UFField();
    $ufField->field_name = "custom_" . $customFieldId;

    $ufField->find();
    while ($ufField->fetch()) {
      //enable/ disable profile
      CRM_Core_BAO_UFField::del($ufField->id);
    }
  }

  /**
   * Function to enable/disable profile field given a custom group id
   *
   * @param int      $customGroupId custom group id
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return void
   * @static
   * @access public
   */
  static function setUFFieldStatus($customGroupId, $is_active) {
    //find the profile id given custom group id
    $queryString = "SELECT civicrm_custom_field.id as custom_field_id
                        FROM   civicrm_custom_field, civicrm_custom_group
                        WHERE  civicrm_custom_field.custom_group_id = civicrm_custom_group.id
                          AND  civicrm_custom_group.id = %1";
    $p = array(1 => array($customGroupId, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($queryString, $p);

    while ($dao->fetch()) {
      //enable/ disable profile
      CRM_Core_BAO_UFField::setUFField($dao->custom_field_id, $is_active);
    }
  }

  /**
   * Function to check the status of custom field used in uf fields
   *
   * @params  int $UFFieldId     uf field id
   *
   * @param $UFFieldId
   *
   * @return boolean   false if custom field are disabled else true
   * @static
   * @access public
   */
  static function checkUFStatus($UFFieldId) {
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
   * Function to find out whether given profile group using Activity
   * Profile fields with contact fields
   */
  static function checkContactActivityProfileType($ufGroupId) {
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
    $profileTypes = array();
    if ($ufGroupType) {
      $typeParts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $ufGroupType);
      $profileTypes = explode(',', $typeParts[0]);
    }

    if (empty($profileTypes)) {
      return FALSE;
    }
    $components = array('Contribution', 'Participant', 'Membership');
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

    $contactTypes = array('Individual', 'Household', 'Organization');
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
   * Function to find out whether given profile group uses $required
   * and/or $optional profile types
   *
   * @param integer $ufGroupId  profile id
   * @param array   $required   array of types those are required
   * @param array   $optional   array of types those are optional
   *
   * @return boolean $valid
   * @static
   */
  static function checkValidProfileType($ufGroupId, $required, $optional = NULL) {
    if (!is_array($required) || empty($required)) {
      return;
    }

    $ufGroup = new CRM_Core_DAO_UFGroup();
    $ufGroup->id = $ufGroupId;
    $ufGroup->find(TRUE);

    $profileTypes = array();
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
   * function to check for mix profile fields (eg: individual + other contact types)
   *
   * @params int     $ufGroupId  uf group id
   * @params boolean $check      this is to check mix profile (if true it will check if profile is
   *                             pure ie. it contains only one contact type)
   *
   * @param $ufGroupId
   *
   * @return  true for mix profile else false
   * @acess public
   * @static
   */
  static function checkProfileType($ufGroupId) {
    $ufGroup = new CRM_Core_DAO_UFGroup();
    $ufGroup->id = $ufGroupId;
    $ufGroup->find(TRUE);

    $profileTypes = array();
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

    $contactTypes = array('Contact', 'Individual', 'Household', 'Organization');
    $components = array('Contribution', 'Participant', 'Membership', 'Activity');
    $fields = array();

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
   * function to get the profile type (eg: individual/organization/household)
   *
   * @param int $ufGroupId     uf group id
   * @param boolean $returnMixType this is true, then field type of  mix profile field is returned
   * @param boolean $onlyPure      true if only pure profiles are required
   *
   * @param bool $skipComponentType
   *
   * @return  profile group_type
   * @acess public
   * @static
   *
   * TODO Why is this function in this class? It seems to be about the UFGroup.
   */
  static function getProfileType($ufGroupId, $returnMixType = TRUE, $onlyPure = FALSE, $skipComponentType = FALSE) {
    $ufGroup = new CRM_Core_DAO_UFGroup();
    $ufGroup->id = $ufGroupId;
    $ufGroup->is_active = 1;

    $ufGroup->find(TRUE);
    return self::calculateProfileType($ufGroup->group_type, $returnMixType, $onlyPure, $skipComponentType);
  }

  /**
   * function to get the profile type (eg: individual/organization/household)
   *
   * @param $ufGroupType
   * @param boolean $returnMixType this is true, then field type of  mix profile field is returned
   * @param boolean $onlyPure      true if only pure profiles are required
   *
   * @param bool $skipComponentType
   *
   * @internal param int $ufGroupId uf group id
   * @return  profile group_type
   * @acess public
   * @static
   *
   * TODO Why is this function in this class? It seems to be about the UFGroup.
   */
  public static function calculateProfileType($ufGroupType, $returnMixType = TRUE, $onlyPure = FALSE, $skipComponentType= FALSE) {
    // profile types
    $contactTypes = array('Contact', 'Individual', 'Household', 'Organization');
    $subTypes = CRM_Contact_BAO_ContactType::subTypes();
    $components = array('Contribution', 'Participant', 'Membership', 'Activity');

    $profileTypes = array();
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
      $componentCount = array();
      foreach ($components as $value) {
        if (in_array($value, $profileTypes)) {
          $componentCount[] = $value;
        }
      }

      //check contact type included in profile
      $contactTypeCount = array();
      foreach ($contactTypes as $value) {
        if (in_array($value, $profileTypes)) {
          $contactTypeCount[] = $value;
        }
      }
      // subtype counter
      $subTypeCount = array();
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
   * function to check for mix profiles groups (eg: individual + other contact types)
   *
   * @param $ctype
   *
   * @return  true for mix profile group else false
   * @acess public
   * @static
   */
  static function checkProfileGroupType($ctype) {
    $ufGroup = new CRM_Core_DAO_UFGroup();

    $query = "
SELECT ufg.id as id
  FROM civicrm_uf_group as ufg, civicrm_uf_join as ufj
 WHERE ufg.id = ufj.uf_group_id
   AND ufj.module = 'User Registration'
   AND ufg.is_active = 1 ";

    $ufGroup = CRM_Core_DAO::executeQuery($query);

    $fields = array();
    $validProfiles = array('Individual', 'Organization', 'Household', 'Contribution');
    while ($ufGroup->fetch()) {
      $profileType = self::getProfileType($ufGroup->id);
      if (in_array($profileType, $validProfiles)) {
        continue;
      }
      elseif ($profileType) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * check for searchable or in selector field for given profile.
   *
   * @params int     $profileID profile id.
   *
   * @param $profileID
   *
   * @return boolean $result    true/false.
   */
  static function checkSearchableORInSelector($profileID) {
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
   *Reset In selector and is seachable values for given $profileID.
   *
   * @params int $profileID profile id.
   *
   * @param $profileID
   *
   * @return void.
   */
  function resetInSelectorANDSearchable($profileID) {
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
   * @param string $key Field key - e.g. street_address-Primary, first_name
   * @param array $profileAddressFields array of profile fields that relate to address fields
   * @param array $profileFilter filter to apply to profile fields - expected usage is to only fill based on
   * the bottom profile per CRM-13726
   *
   * @return bool Can the address block be hidden safe in the knowledge all fields are elsewhere collected (see CRM-15118)
   */
  static function assignAddressField($key, &$profileAddressFields, $profileFilter) {
    $billing_id = CRM_Core_BAO_LocationType::getBilling();
    list($prefixName, $index) = CRM_Utils_System::explode('-', $key, 2);

    $profileFields = civicrm_api3('uf_field', 'get', array_merge($profileFilter,
      array('is_active' => 1, 'return' => 'field_name, is_required', 'options' => array(
        'limit' => 0,
      ))
    ));
    //check for valid fields ( fields that are present in billing block )
    $validBillingFields = array(
      'first_name',
      'middle_name',
      'last_name',
      'street_address',
      'supplemental_address_1',
      'city',
      'state_province',
      'postal_code',
      'country'
    );
    $requiredBillingFields = array_diff($validBillingFields, array('middle_name','supplemental_address_1'));
    $validProfileFields = array();
    $requiredProfileFields = array();

    foreach ($profileFields['values'] as $field) {
      if(in_array($field['field_name'], $validBillingFields)) {
        $validProfileFields[] = $field['field_name'];
      }
      if ($field['is_required']) {
        $requiredProfileFields[] = $field['field_name'];
      }
    }

    if (!in_array($prefixName, $validProfileFields) ) {
      return;
    }

    if (!empty($index) && (
      // it's empty so we set it OR
      !CRM_Utils_array::value($prefixName, $profileAddressFields)
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
    CRM_Core_Resources::singleton()->addSetting(array('billing' => array('billingProfileIsHideable' => empty($potentiallyMissingRequiredFields))));
  }

  /**
   * Get a list of fields which can be added to profiles
   *
   * @param int $gid: UF group ID
   * @param array $defaults: Form defaults
   * @return array, multidimensional; e.g. $result['FieldGroup']['field_name']['label']
   * @static
   */
  public static function getAvailableFields($gid = NULL, $defaults = array()) {
    $fields = array(
      'Contact' => array(),
      'Individual' => CRM_Contact_BAO_Contact::importableFields('Individual', FALSE, FALSE, TRUE, TRUE, TRUE),
      'Household' => CRM_Contact_BAO_Contact::importableFields('Household', FALSE, FALSE, TRUE, TRUE, TRUE),
      'Organization' => CRM_Contact_BAO_Contact::importableFields('Organization', FALSE, FALSE, TRUE, TRUE, TRUE),
    );

    // include hook injected fields
    $fields['Contact'] = array_merge($fields['Contact'], CRM_Contact_BAO_Query_Hook::singleton()->getFields());

    // add current employer for individuals
    $fields['Individual']['current_employer'] = array(
      'name' => 'organization_name',
      'title' => ts('Current Employer'),
    );

    $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options', TRUE, NULL, TRUE
    );

    if (!$addressOptions['county']) {
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
    $fields['Contact']['phone_and_ext'] = array(
      'name' => 'phone_and_ext',
      'title' => ts('Phone and Extension'),
      'hasLocationType' => 1,
    );

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
        $contribFields['contribution_note'] = array(
          'name' => 'contribution_note',
          'title' => ts('Contribution Note'),
        );
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
          'case_start_date',
          'case_end_date',
          'case_role',
          'case_status',
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

    $fields['Formatting']['format_free_html_' . rand(1000, 9999)] = array(
      'name' => 'free_html',
      'import' => FALSE,
      'export' => FALSE,
      'title' => 'Free HTML',
    );

    // Sort by title
    foreach ($fields as &$values) {
      $values = CRM_Utils_Array::crmArraySortByField($values, 'title');
    }

    //group selected and unwanted fields list
    $ufFields = $gid ? CRM_Core_BAO_UFGroup::getFields($gid, FALSE, NULL, NULL, NULL, TRUE, NULL, TRUE) : array();
    $groupFieldList = array_merge($ufFields, array(
      'note',
      'email_greeting_custom',
      'postal_greeting_custom',
      'addressee_custom',
      'id',
    ));
    //unset selected fields
    foreach ($groupFieldList as $key => $value) {
      if (is_integer($key)) {
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

    return $fields;
  }

  /**
   * Get a list of fields which can be added to profiles
   *
   * @param bool $force
   *
   * @return array, multidimensional; e.g. $result['field_name']['label']
   * @static
   */
  public static function getAvailableFieldsFlat($force = FALSE) {
    // FIXME reset when data model changes
    static $result = NULL;
    if ($result === NULL || $force) {
      $fieldTree = self::getAvailableFields();
      $result = array();
      foreach ($fieldTree as $field_type => $fields) {
        foreach ($fields as $field_name => $field) {
          if (!isset($result[$field_name])) {
            $field['field_type'] = $field_type;
            $result[$field_name] = $field;
          }
        }
      }
    }
    return $result;
  }

  /**
   * Determine whether the given field_name is valid
   *
   * @param string $fieldName
   * @return bool
   */
  static function isValidFieldName($fieldName) {
    $availableFields = CRM_Core_BAO_UFField::getAvailableFieldsFlat();
    return isset($availableFields[$fieldName]);
  }

  /**
   * @return array|null
   */
  static function getContribBatchEntryFields() {
    if (self::$_contriBatchEntryFields === NULL) {
      self::$_contriBatchEntryFields = array(
        'send_receipt' => array(
          'name' => 'send_receipt',
          'title' => ts('Send Receipt'),
        ),
        'soft_credit' => array(
          'name' => 'soft_credit',
          'title' => ts('Soft Credit'),
        ),
        'soft_credit_type' => array(
          'name' => 'soft_credit_type',
          'title' => ts('Soft Credit Type'),
        ),
        'product_name' => array(
          'name' => 'product_name',
          'title' => ts('Premiums'),
        ),
        'contribution_note' => array(
          'name' => 'contribution_note',
          'title' => ts('Contribution Note'),
        ),
      );
    }
    return self::$_contriBatchEntryFields;
  }

  /**
   * @return array|null
   */
  public static function getMemberBatchEntryFields() {
    if (self::$_memberBatchEntryFields === NULL) {
      self::$_memberBatchEntryFields = array(
        'send_receipt' => array(
          'name' => 'send_receipt',
          'title' => ts('Send Receipt'),
        ),
        'soft_credit' => array(
          'name' => 'soft_credit',
          'title' => ts('Soft Credit'),
        ),
        'product_name' => array(
          'name' => 'product_name',
          'title' => ts('Premiums'),
        ),
        'financial_type' => array(
          'name' => 'financial_type',
          'title' => ts('Financial Type'),
        ),
        'total_amount' => array(
          'name' => 'total_amount',
          'title' => ts('Total Amount'),
        ),
        'receive_date' => array(
          'name' => 'receive_date',
          'title' => ts('Receive Date'),
        ),
        'payment_instrument' => array(
          'name' => 'payment_instrument',
          'title' => ts('Payment Instrument'),
        ),
        'contribution_status_id' => array(
          'name' => 'contribution_status_id',
          'title' => ts('Contribution Status'),
        ),
      );
    }
    return self::$_memberBatchEntryFields;
  }
}

