<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Core_BAO_Mapping extends CRM_Core_DAO_Mapping {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params      (reference ) an assoc array of name/value pairs
   * @param array $defaults    (reference ) an assoc array to hold the flattened values
   *
   * @return object     CRM_Core_DAO_Mapping object on success, otherwise null
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $mapping = new CRM_Core_DAO_Mapping();
    $mapping->copyValues($params);
    if ($mapping->find(TRUE)) {
      CRM_Core_DAO::storeValues($mapping, $defaults);
      return $mapping;
    }
    return NULL;
  }

  /**
   * Function to delete the mapping
   *
   * @param int $id   mapping id
   *
   * @return boolean
   * @access public
   * @static
   *
   */
  static function del($id) {
    // delete from mapping_field table
    $mappingField = new CRM_Core_DAO_MappingField();
    $mappingField->mapping_id = $id;
    $mappingField->find();
    while ($mappingField->fetch()) {
      $mappingField->delete();
    }

    // delete from mapping table
    $mapping = new CRM_Core_DAO_Mapping();
    $mapping->id = $id;
    $mapping->delete();
    CRM_Core_Session::setStatus(ts('Selected mapping has been deleted successfully.'), ts('Deleted'), 'success');

    return TRUE;
  }

  /**
   * takes an associative array and creates a contact object
   *
   * The function extract all the params it needs to initialize the create a
   * contact object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array  $params         (reference) an assoc array of name/value pairs
   *
   * @return object    CRM_Core_DAO_Mapper object on success, otherwise null
   * @access public
   * @static
   */
  static function add(&$params) {
    $mapping = new CRM_Core_DAO_Mapping();
    $mapping->copyValues($params);
    $mapping->save();

    return $mapping;
  }

  /**
   * function to get the list of mappings
   *
   * @params string  $mappingTypeId  mapping type id
   *
   * @return array $mapping array of mapping name
   * @access public
   * @static
   */
  static function getMappings($mappingTypeId) {
    $mapping = array();
    $mappingDAO = new CRM_Core_DAO_Mapping();
    $mappingDAO->mapping_type_id = $mappingTypeId;
    $mappingDAO->find();

    while ($mappingDAO->fetch()) {
      $mapping[$mappingDAO->id] = $mappingDAO->name;
    }

    return $mapping;
  }

  /**
   * function to get the mapping fields
   *
   * @params int $mappingId  mapping id
   *
   * @return array $mappingFields array of mapping fields
   * @access public
   * @static
   *
   */
  static function getMappingFields($mappingId) {
    //mapping is to be loaded from database
    $mapping = new CRM_Core_DAO_MappingField();
    $mapping->mapping_id = $mappingId;
    $mapping->orderBy('column_number');
    $mapping->find();

    $mappingName = $mappingLocation = $mappingContactType = $mappingPhoneType = array();
    $mappingImProvider = $mappingRelation = $mappingOperator = $mappingValue = $mappingWebsiteType = array();
    while ($mapping->fetch()) {
      $mappingName[$mapping->grouping][$mapping->column_number] = $mapping->name;
      $mappingContactType[$mapping->grouping][$mapping->column_number] = $mapping->contact_type;

      if (!empty($mapping->location_type_id)) {
        $mappingLocation[$mapping->grouping][$mapping->column_number] = $mapping->location_type_id;
      }

      if (!empty($mapping->phone_type_id)) {
        $mappingPhoneType[$mapping->grouping][$mapping->column_number] = $mapping->phone_type_id;
      }

      // get IM service provider type id from mapping fields
      if (!empty($mapping->im_provider_id)) {
        $mappingImProvider[$mapping->grouping][$mapping->column_number] = $mapping->im_provider_id;
      }

      if (!empty($mapping->website_type_id)) {
        $mappingWebsiteType[$mapping->grouping][$mapping->column_number] = $mapping->website_type_id;
      }

      if (!empty($mapping->relationship_type_id)) {
        $mappingRelation[$mapping->grouping][$mapping->column_number] = "{$mapping->relationship_type_id}_{$mapping->relationship_direction}";
      }

      if (!empty($mapping->operator)) {
        $mappingOperator[$mapping->grouping][$mapping->column_number] = $mapping->operator;
      }

      if (!empty($mapping->value)) {
        $mappingValue[$mapping->grouping][$mapping->column_number] = $mapping->value;
      }
    }

    return array(
      $mappingName, $mappingContactType, $mappingLocation, $mappingPhoneType,
      $mappingImProvider, $mappingRelation, $mappingOperator, $mappingValue, $mappingWebsiteType,
    );
  }

  /**
   *function to check Duplicate Mapping Name
   *
   * @params $nameField  string mapping Name
   *
   * @params $mapTypeId string mapping Type
   *
   * @return boolean
   *
   */
  static function checkMapping($nameField, $mapTypeId) {
    $mapping = new CRM_Core_DAO_Mapping();
    $mapping->name = $nameField;
    $mapping->mapping_type_id = $mapTypeId;
    if ($mapping->find(TRUE)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Function returns associated array of elements, that will be passed for search
   *
   * @params int $smartGroupId smart group id
   *
   * @return $returnFields  associated array of elements
   *
   * @static
   * @public
   */
  static function getFormattedFields($smartGroupId) {
    $returnFields = array();

    //get the fields from mapping table
    $dao = new CRM_Core_DAO_MappingField();
    $dao->mapping_id = $smartGroupId;
    $dao->find();
    while ($dao->fetch()) {
      $fldName = $dao->name;
      if ($dao->location_type_id) {
        $fldName .= "-{$dao->location_type_id}";
      }
      if ($dao->phone_type) {
        $fldName .= "-{$dao->phone_type}";
      }
      $returnFields[$fldName]['value'] = $dao->value;
      $returnFields[$fldName]['op'] = $dao->operator;
      $returnFields[$fldName]['grouping'] = $dao->grouping;
    }
    return $returnFields;
  }

  /**
   * Function to build the mapping form
   *
   * @params object $form        form object
   * @params string $mappingType mapping type (Export/Import/Search Builder)
   * @params int    $mappingId   mapping id
   * @params mixed  $columnCount column count is int for and array for search builder
   * @params int    $blockCount  block count (no of blocks shown)
   *
   * @return none
   * @access public
   * @static
   */
  static function buildMappingForm(&$form, $mappingType = 'Export', $mappingId = NULL, $columnNo, $blockCount = 3, $exportMode = NULL) {
    if ($mappingType == 'Export') {
      $name = "Map";
      $columnCount = array('1' => $columnNo);
    }
    elseif ($mappingType == 'Search Builder') {
      $name = "Builder";
      $columnCount = $columnNo;
    }

    //get the saved mapping details

    if ($mappingType == 'Export') {
      $form->applyFilter('saveMappingName', 'trim');

      //to save the current mappings
      if (!isset($mappingId)) {
        $saveDetailsName = ts('Save this field mapping');
        $form->add('text', 'saveMappingName', ts('Name'));
        $form->add('text', 'saveMappingDesc', ts('Description'));
      }
      else {
        $form->assign('loadedMapping', $mappingId);

        $params         = array('id' => $mappingId);
        $temp           = array();
        $mappingDetails = CRM_Core_BAO_Mapping::retrieve($params, $temp);

        $form->assign('savedName', $mappingDetails->name);

        $form->add('hidden', 'mappingId', $mappingId);

        $form->addElement('checkbox', 'updateMapping', ts('Update this field mapping'), NULL);
        $saveDetailsName = ts('Save as a new field mapping');
        $form->add('text', 'saveMappingName', ts('Name'));
        $form->add('text', 'saveMappingDesc', ts('Description'));
      }

      $form->addElement('checkbox', 'saveMapping', $saveDetailsName, NULL, array('onclick' => "showSaveDetails(this)"));
      $form->addFormRule(array('CRM_Export_Form_Map', 'formRule'), $form->get('mappingTypeId'));
    }
    elseif ($mappingType == 'Search Builder') {
      $form->addElement('submit', 'addBlock', ts('Also include contacts where'),
        array('class' => 'submit-link')
      );
    }

    $defaults         = array();
    $hasLocationTypes = array();
    $hasRelationTypes = array();
    $fields           = array();

    if ($mappingType == 'Export') {
      $required = TRUE;
    }
    elseif ($mappingType == 'Search Builder') {
      $required = FALSE;
    }

    $contactType = array('Individual', 'Household', 'Organization');
    foreach ($contactType as $value) {
      $contactFields = CRM_Contact_BAO_Contact::exportableFields($value, FALSE, $required);
      // exclude the address options disabled in the Address Settings
      $fields[$value] = CRM_Core_BAO_Address::validateAddressOptions($contactFields);

      if ($mappingType == 'Export') {
        $relationships = array();
        $relationshipTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, $value);
        asort($relationshipTypes);

        foreach ($relationshipTypes as $key => $var) {
          list($type) = explode('_', $key);

          $relationships[$key]['title'] = $var;
          $relationships[$key]['headerPattern'] = '/' . preg_quote($var, '/') . '/';
          $relationships[$key]['export'] = TRUE;
          $relationships[$key]['relationship_type_id'] = $type;
          $relationships[$key]['related'] = TRUE;
          $relationships[$key]['hasRelationType'] = 1;
        }

        if (!empty($relationships)) {
          $fields[$value] = array_merge($fields[$value],
            array('related' => array('title' => ts('- related contact info -'))),
            $relationships
          );
        }
      }
    }

    //get the current employer for mapping.
    if ($required) {
      $fields['Individual']['current_employer']['title'] = ts('Current Employer');
    }

    // add component fields
    $compArray = array();

    //we need to unset groups, tags, notes for component export
    if ($exportMode != CRM_Export_Form_Select::CONTACT_EXPORT) {
      foreach (array(
        'groups', 'tags', 'notes') as $value) {
        unset($fields['Individual'][$value]);
        unset($fields['Household'][$value]);
        unset($fields['Organization'][$value]);
      }
    }

    if ($mappingType == 'Search Builder') {
      //build the common contact fields array.
      $fields['Contact'] = array();
      foreach ($fields['Individual'] as $key => $value) {
        if (CRM_Utils_Array::value($key, $fields['Household']) &&
            CRM_Utils_Array::value($key, $fields['Organization'])
            ) {
          $fields['Contact'][$key] = $value;
          unset($fields['Organization'][$key],
                $fields['Household'][$key],
                $fields['Individual'][$key]);
        }
      }
      if (array_key_exists('note', $fields['Contact'])) {
        $noteTitle = $fields['Contact']['note']['title'];
        $fields['Contact']['note']['title'] = $noteTitle . ': ' . ts('Body and Subject');
        $fields['Contact']['note_body']    = array( 'title' => $noteTitle . ': ' . ts('Body only'),    'name' => 'note_body' );
        $fields['Contact']['note_subject'] = array( 'title' => $noteTitle . ': ' . ts('Subject only'), 'name' => 'note_subject' );
      }
    }

    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::CONTRIBUTE_EXPORT)) {
      if (CRM_Core_Permission::access('CiviContribute')) {
        $fields['Contribution'] = CRM_Contribute_BAO_Contribution::exportableFields();
        unset($fields['Contribution']['contribution_contact_id']);
        unset($fields['Contribution']['contribution_status_id']);
        $compArray['Contribution'] = ts('Contribution');
      }
    }

    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::EVENT_EXPORT)) {
      if (CRM_Core_Permission::access('CiviEvent')) {
        $fields['Participant'] = CRM_Event_BAO_Participant::exportableFields();
        //get the component payment fields
        if ($exportMode == CRM_Export_Form_Select::EVENT_EXPORT) {
          require_once 'CRM/Export/BAO/Export.php';
          $componentPaymentFields = array();
          foreach (CRM_Export_BAO_Export::componentPaymentFields() as $payField => $payTitle) {
            $componentPaymentFields[$payField] = array('title' => $payTitle);
          }
          $fields['Participant'] = array_merge($fields['Participant'], $componentPaymentFields);
        }

        unset($fields['Participant']['participant_contact_id']);
        $compArray['Participant'] = ts('Participant');
      }
    }

    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::MEMBER_EXPORT)) {
      if (CRM_Core_Permission::access('CiviMember')) {
        $fields['Membership'] = CRM_Member_BAO_Membership::getMembershipFields($exportMode);
        unset($fields['Membership']['membership_contact_id']);
        $compArray['Membership'] = ts('Membership');
      }
    }

    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::PLEDGE_EXPORT)) {
      if (CRM_Core_Permission::access('CiviPledge')) {
        $fields['Pledge'] = CRM_Pledge_BAO_Pledge::exportableFields();
        unset($fields['Pledge']['pledge_contact_id']);
        $compArray['Pledge'] = ts('Pledge');
      }
    }

    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::CASE_EXPORT)) {
      if (CRM_Core_Permission::access('CiviCase')) {
        $fields['Case'] = CRM_Case_BAO_Case::exportableFields();
        $compArray['Case'] = ts('Case');

        $fields['Activity'] = CRM_Activity_BAO_Activity::exportableFields('Case');
        $compArray['Activity'] = ts('Case Activity');

        unset($fields['Case']['case_contact_id']);
      }
    }
    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::GRANT_EXPORT)) {
      if (CRM_Core_Permission::access('CiviGrant')) {
        $fields['Grant'] = CRM_Grant_BAO_Grant::exportableFields();
        unset($fields['Grant']['grant_contact_id']);
        $compArray['Grant'] = ts('Grant');
      }
    }

    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::ACTIVITY_EXPORT)) {
      $fields['Activity'] = CRM_Activity_BAO_Activity::exportableFields('Activity');
      $compArray['Activity'] = ts('Activity');
    }

    //Contact Sub Type For export
    $contactSubTypes = array();
    $subTypes = CRM_Contact_BAO_ContactType::subTypeInfo();

    foreach ($subTypes as $subType => $val) {
      //adding subtype specific relationships CRM-5256
      $csRelationships = array();

      if ($mappingType == 'Export') {
        $subTypeRelationshipTypes = 
          CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, $val['parent'],
                                                                   FALSE, 'label', TRUE, $subType);
        
        foreach ($subTypeRelationshipTypes as $key => $var) {
          if (!array_key_exists($key, $fields[$val['parent']])) {
            list($type) = explode('_', $key);
            
            $csRelationships[$key]['title'] = $var;
            $csRelationships[$key]['headerPattern'] = '/' . preg_quote($var, '/') . '/';
            $csRelationships[$key]['export'] = TRUE;
            $csRelationships[$key]['relationship_type_id'] = $type;
            $csRelationships[$key]['related'] = TRUE;
            $csRelationships[$key]['hasRelationType'] = 1;
          }
        }
      }

      $fields[$subType] = $fields[$val['parent']] + $csRelationships;

      //custom fields for sub type
      $subTypeFields = CRM_Core_BAO_CustomField::getFieldsForImport($subType);
      $fields[$subType] += $subTypeFields;

      if (!empty($subTypeFields) || !empty($csRelationships)) {
        $contactSubTypes[$subType] = $val['label'];
      }
    }

    unset($subTypes);

    foreach ($fields as $key => $value) {

      foreach ($value as $key1 => $value1) {
        //CRM-2676, replacing the conflict for same custom field name from different custom group.
        $customGroupName = self::getCustomGroupName($key1);

        if ($customGroupName) {
          $relatedMapperFields[$key][$key1] = $mapperFields[$key][$key1] = $customGroupName . ': ' . $value1['title'];
        }
        else {
          $relatedMapperFields[$key][$key1] = $mapperFields[$key][$key1] = $value1['title'];
        }
        if (isset($value1['hasLocationType'])) {
          $hasLocationTypes[$key][$key1] = $value1['hasLocationType'];
        }

        if (isset($value1['hasRelationType'])) {
          $hasRelationTypes[$key][$key1] = $value1['hasRelationType'];
          unset($relatedMapperFields[$key][$key1]);
        }
      }

      if (array_key_exists('related', $relatedMapperFields[$key])) {
        unset($relatedMapperFields[$key]['related']);
      }
    }

    $mapperKeys = array_keys($mapperFields);

    $locationTypes = CRM_Core_PseudoConstant::locationType();

    $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();

    /* FIXME: dirty hack to make the default option show up first.  This
         * avoids a mozilla browser bug with defaults on dynamically constructed
         * selector widgets. */


    if ($defaultLocationType) {
      $defaultLocation = $locationTypes[$defaultLocationType->id];
      unset($locationTypes[$defaultLocationType->id]);
      $locationTypes = array(
        $defaultLocationType->id => $defaultLocation) + $locationTypes;
    }

    $locationTypes = array(
      ' ' => ts('Primary')) + $locationTypes;

    // since we need a hierarchical list to display contact types & subtypes,
    // this is what we going to display in first selector
    $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements(FALSE, FALSE);
    if ($mappingType == 'Search Builder') {
      $contactTypes = array('Contact' => ts('Contacts')) + $contactTypes;
    }

    $sel1 =
      array('' => ts('- select record type -')) + $contactTypes + $compArray;

    foreach ($sel1 as $key => $sel) {
      if ($key) {
        asort($mapperFields[$key]);
        $sel2[$key] = array('' => ts('- select field -')) + $mapperFields[$key];
      }
    }

    $sel3['']    = NULL;
    $sel5['']    = NULL;
    $phoneTypes  = CRM_Core_PseudoConstant::phoneType();
    $imProviders = CRM_Core_PseudoConstant::IMProvider();
    asort($phoneTypes);

    foreach ($sel1 as $k => $sel) {
      if ($k) {
        foreach ($locationTypes as $key => $value) {
          if (trim($key) != '') {
            $sel4[$k]['phone'][$key] = &$phoneTypes;
            $sel4[$k]['im'][$key] = &$imProviders;
          }
        }
      }
    }

    foreach ($sel1 as $k => $sel) {
      if ($k) {
        foreach ($mapperFields[$k] as $key => $value) {
          if (isset($hasLocationTypes[$k][$key])) {
            $sel3[$k][$key] = $locationTypes;
          }
          else {
            $sel3[$key] = NULL;
          }
        }
      }
    }

    //Array for core fields and relationship custom data

    $relationshipTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, NULL, TRUE);

    if ($mappingType == 'Export') {
      foreach ($sel1 as $k => $sel) {
        if ($k) {
          foreach ($mapperFields[$k] as $field => $dontCare) {
            if (isset($hasRelationTypes[$k][$field])) {
              list($id, $first, $second) = explode('_', $field);
              // FIX ME: For now let's not expose custom data related to relationship
              $relationshipCustomFields = array();
              //$relationshipCustomFields    = self::getRelationTypeCustomGroupData( $id );
              //asort( $relationshipCustomFields ) ;

              $relationshipType = new CRM_Contact_BAO_RelationshipType();
              $relationshipType->id = $id;
              if ($relationshipType->find(TRUE)) {
                $direction = "contact_sub_type_$second";
                if (isset($relationshipType->$direction)) {
                  $relatedFields = array_merge((array)$relatedMapperFields[$relationshipType->$direction], (array)$relationshipCustomFields);
                }
                else {
                  $target_type = 'contact_type_' . $second;
                  $relatedFields = array_merge((array)$relatedMapperFields[$relationshipType->$target_type], (array)$relationshipCustomFields);
                }
              }
              $relationshipType->free();
              asort($relatedFields);
              $sel5[$k][$field] = $relatedFields;
            }
          }
        }
      }

      //Location Type for relationship fields

      foreach ($sel5 as $k => $v) {
        if ($v) {
          foreach ($v as $rel => $fields) {
            foreach ($fields as $field => $fieldLabel) {
              if (isset($hasLocationTypes[$k][$field])) {
                $sel6[$k][$rel][$field] = $locationTypes;
              }
            }
          }
        }
      }

      //PhoneTypes for  relationship fields
      $sel7[''] = NULL;
      foreach ($sel6 as $k => $rel) {
        if ($k) {
          foreach ($rel as $phonekey => $phonevalue) {
            foreach ($locationTypes as $locType => $loc) {
              if (trim($locType) != '') {
                $sel7[$k][$phonekey]['phone'][$locType] = &$phoneTypes;
                $sel7[$k][$phonekey]['im'][$locType] = &$imProviders;
              }
            }
          }
        }
      }
    }

    //special fields that have location, hack for primary location
    $specialFields = array(
      'street_address', 'supplemental_address_1', 'supplemental_address_2',
      'city', 'postal_code', 'postal_code_suffix', 'geo_code_1', 'geo_code_2',
      'state_province', 'country', 'phone', 'email', 'im',
    );

    if (isset($mappingId)) {
      $colCnt = 0;

      list($mappingName, $mappingContactType, $mappingLocation, $mappingPhoneType, $mappingImProvider,
        $mappingRelation, $mappingOperator, $mappingValue
      ) = CRM_Core_BAO_Mapping::getMappingFields($mappingId);

      $blkCnt = count($mappingName);
      if ($blkCnt >= $blockCount) {
        $blockCount = $blkCnt + 1;
      }
      for ($x = 1; $x < $blockCount; $x++) {
        if (isset($mappingName[$x])) {
          $colCnt = count($mappingName[$x]);
          if ($colCnt >= $columnCount[$x]) {
            $columnCount[$x] = $colCnt;
          }
        }
      }
    }

    $form->_blockCount = $blockCount;
    $form->_columnCount = $columnCount;

    $form->set('blockCount', $form->_blockCount);
    $form->set('columnCount', $form->_columnCount);

    $defaults = $noneArray = $nullArray = array();

    //used to warn for mismatch column count or mismatch mapping
    $warning = 0;
    for ($x = 1; $x < $blockCount; $x++) {

      for ($i = 0; $i < $columnCount[$x]; $i++) {

        $sel = &$form->addElement('hierselect', "mapper[$x][$i]", ts('Mapper for Field %1', array(1 => $i)), NULL);
        $jsSet = FALSE;

        if (isset($mappingId)) {
          $locationId = isset($mappingLocation[$x][$i]) ? $mappingLocation[$x][$i] : 0;
          if (isset($mappingName[$x][$i])) {
            if (is_array($mapperFields[$mappingContactType[$x][$i]])) {

              if (isset($mappingRelation[$x][$i])) {

                $contactDetails = strtolower(str_replace(" ", "_", $mappingName[$x][$i]));
                $relLocationId = isset($mappingLocation[$x][$i]) ? $mappingLocation[$x][$i] : 0;
                if (!$relLocationId && in_array($mappingName[$x][$i], $specialFields)) {
                  $relLocationId = " ";
                }

                $relPhoneType = isset($mappingPhoneType[$x][$i]) ? $mappingPhoneType[$x][$i] : NULL;

                $defaults["mapper[$x][$i]"] = array(
                  $mappingContactType[$x][$i],
                  $mappingRelation[$x][$i],
                  $locationId,
                  $phoneType,
                  $mappingName[$x][$i],
                  $relLocationId,
                  $relPhoneType,
                );

                if (!$locationId) {
                  $noneArray[] = array($x, $i, 2);
                }
                if (!$phoneType && !$imProvider) {
                  $noneArray[] = array($x, $i, 3);
                }
                if (!$mappingName[$x][$i]) {
                  $noneArray[] = array($x, $i, 4);
                }
                if (!$relLocationId) {
                  $noneArray[] = array($x, $i, 5);
                }
                if (!$relPhoneType) {
                  $noneArray[] = array($x, $i, 6);
                }
                $noneArray[] = array($x, $i, 2);
              }
              else {
                $phoneType = isset($mappingPhoneType[$x][$i]) ? $mappingPhoneType[$x][$i] : NULL;
                $imProvider = isset($mappingImProvider[$x][$i]) ? $mappingImProvider[$x][$i] : NULL;
                if (!$locationId && in_array($mappingName[$x][$i], $specialFields)) {
                  $locationId = " ";
                }

                $defaults["mapper[$x][$i]"] = array(
                  $mappingContactType[$x][$i],
                  $mappingName[$x][$i],
                  $locationId,
                  $phoneType,
                );
                if (!$mappingName[$x][$i]) {
                  $noneArray[] = array($x, $i, 1);
                }
                if (!$locationId) {
                  $noneArray[] = array($x, $i, 2);
                }
                if (!$phoneType && !$imProvider) {
                  $noneArray[] = array($x, $i, 3);
                }

                $noneArray[] = array($x, $i, 4);
                $noneArray[] = array($x, $i, 5);
                $noneArray[] = array($x, $i, 6);
              }

              $jsSet = TRUE;

              if (CRM_Utils_Array::value($i, CRM_Utils_Array::value($x, $mappingOperator))) {
                $defaults["operator[$x][$i]"] = CRM_Utils_Array::value($i, $mappingOperator[$x]);
              }

              if (CRM_Utils_Array::value($i, CRM_Utils_Array::value($x, $mappingValue))) {
                $defaults["value[$x][$i]"] = CRM_Utils_Array::value($i, $mappingValue[$x]);
              }
            }
          }
        }
        //Fix for Search Builder
        if ($mappingType == 'Export') {
          $j = 7;
        }
        else {
          $j = 4;
        }

        $formValues = $form->exportValues();
        if (!$jsSet) {
          if (empty($formValues)) {
            // Incremented length for third select box(relationship type)
            for ($k = 1; $k < $j; $k++) {
              $noneArray[] = array($x, $i, $k);
            }
          }
          else {
            if (!empty($formValues['mapper'][$x])) {
              foreach ($formValues['mapper'][$x] as $value) {
                for ($k = 1; $k < $j; $k++) {
                  if (!isset($formValues['mapper'][$x][$i][$k]) ||
                    (!$formValues['mapper'][$x][$i][$k])
                  ) {
                    $noneArray[] = array($x, $i, $k);
                  }
                  else {
                    $nullArray[] = array($x, $i, $k);
                  }
                }
              }
            }
            else {
              for ($k = 1; $k < $j; $k++) {
                $noneArray[] = array($x, $i, $k);
              }
            }
          }
        }
        //Fix for Search Builder
        if ($mappingType == 'Export') {
          if (!isset($mappingId)) {
            if (isset($formValues['mapper']) &&
              isset($formValues['mapper'][$x][$i][1]) &&
              array_key_exists($formValues['mapper'][$x][$i][1], $relationshipTypes)
            ) {
              $sel->setOptions(array($sel1, $sel2, $sel5, $sel6, $sel7, $sel3, $sel4));
            }
            else {
              $sel->setOptions(array($sel1, $sel2, $sel3, $sel4, $sel5, $sel6, $sel7));
            }
          }
          else {
            $sel->setOptions(array($sel1, $sel2, $sel3, $sel4, $sel5, $sel6, $sel7));
          }
        }
        else {
          $sel->setOptions(array($sel1, $sel2, $sel3, $sel4));
        }

        if ($mappingType == 'Search Builder') {
          //CRM -2292, restricted array set
          $operatorArray = array(
            ''             => ts('-operator-'),
            '='            => '=',
            '!='           => '!=',
            '>'            => '>',
            '<'            => '<',
            '>='           => '>=',
            '<='           => '<=',
            'IN'           => 'IN',
            'LIKE'         => 'LIKE',
            'RLIKE'        => 'RLIKE',
            'IS EMPTY'     => 'IS EMPTY',
            'IS NOT EMPTY' => 'IS NOT EMPTY',
            'IS NULL'      => 'IS NULL',
            'IS NOT NULL'  => 'IS NOT NULL',
          );

          $form->add('select', "operator[$x][$i]", '', $operatorArray);
          $form->add('text', "value[$x][$i]", '');
        }
      }
      //end of columnCnt for
      if ($mappingType == 'Search Builder') {
        $title = ts('Another search field');
      }
      else {
        $title = ts('Select more fields');
      }

      $form->addElement('submit', "addMore[$x]", $title, array('class' => 'submit-link'));
    }
    //end of block for

    $js = "<script type='text/javascript'>\n";
    $formName = "document.{$name}";
    if (!empty($nullArray)) {
      $js      .= "var nullArray = [";
      $elements = array();
      $seen     = array();
      foreach ($nullArray as $element) {
        $key = "{$element[0]}, {$element[1]}, {$element[2]}";
        if (!isset($seen[$key])) {
          $elements[] = "[$key]";
          $seen[$key] = 1;
        }
      }
      $js .= implode(', ', $elements);
      $js .= "]";
      $js .= "
                for(var i=0;i<nullArray.length;i++) {
                    if ( {$formName}['mapper['+nullArray[i][0]+']['+nullArray[i][1]+']['+nullArray[i][2]+']'] ) {
                        {$formName}['mapper['+nullArray[i][0]+']['+nullArray[i][1]+']['+nullArray[i][2]+']'].style.display = '';
                    }
                }
";
    }
    if (!empty($noneArray)) {
      $js      .= "var noneArray = [";
      $elements = array();
      $seen     = array();
      foreach ($noneArray as $element) {
        $key = "{$element[0]}, {$element[1]}, {$element[2]}";
        if (!isset($seen[$key])) {
          $elements[] = "[$key]";
          $seen[$key] = 1;
        }
      }
      $js .= implode(', ', $elements);
      $js .= "]";
      $js .= "
                for(var i=0;i<noneArray.length;i++) {
                    if ( {$formName}['mapper['+noneArray[i][0]+']['+noneArray[i][1]+']['+noneArray[i][2]+']'] ) {
  {$formName}['mapper['+noneArray[i][0]+']['+noneArray[i][1]+']['+noneArray[i][2]+']'].style.display = 'none';
                    }
                }
";
    }
    $js .= "</script>\n";

    $form->assign('initHideBoxes', $js);
    $form->assign('columnCount', $columnCount);
    $form->assign('blockCount', $blockCount);
    $form->setDefaults($defaults);

    $form->setDefaultAction('refresh');
  }

  /* Function returns all custom fields with group title and
   * field label
   * @relationshipTypeId related relationship type id
   * @return $groupTitle all custom field titles
   */
  function getRelationTypeCustomGroupData($relationshipTypeId) {

    $customFields = CRM_Core_BAO_CustomField::getFields('Relationship', NULL, NULL, $relationshipTypeId, NULL, NULL);
    $groupTitle = array();
    foreach ($customFields as $krelation => $vrelation) {
      $groupTitle[$vrelation['label']] = $vrelation['groupTitle'] . '...' . $vrelation['label'];
    }
    return $groupTitle;
  }


  /**
   * Function returns all  Custom group Names
   *
   * @param customfieldId related custom field id
   * @return $customGroupName all custom group names
   * @static
   */
  static function getCustomGroupName($customfieldId) {
    if ($customFieldId = CRM_Core_BAO_CustomField::getKeyID($customfieldId)) {
      $customGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $customFieldId, 'custom_group_id');
      $customGroupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'title');

      if (strlen($customGroupName) > 13) {
        $customGroupName = substr($customGroupName, 0, 10) . '...';
      }

      return $customGroupName;
    }
  }

  /**
   * Function returns associated array of elements, that will be passed for search
   *
   * @params array   $params associated array of submitted values
   * @params boolean $row    row no of the fields
   *
   * @return $returnFields  formatted associated array of elements
   *
   * @static
   * @public
   */
  static function &formattedFields(&$params, $row = FALSE) {
    $fields = array();

    if (empty($params) || !isset($params['mapper'])) {
      return $fields;
    }

    $types = array('Individual', 'Organization', 'Household');
    foreach ($params['mapper'] as $key => $value) {
      $contactType = NULL;
      foreach ($value as $k => $v) {
        if (in_array($v[0], $types)) {
          if ($contactType && $contactType != $v[0]) {
            CRM_Core_Error::fatal(ts("Cannot have two clauses with different types: %1, %2",
                array(1 => $contactType, 2 => $v[0])
              ));
          }
          $contactType = $v[0];
        }
        if (CRM_Utils_Array::value('1', $v)) {
          $fldName = $v[1];
          $v2 = CRM_Utils_Array::value('2', $v);
          if ($v2 && trim($v2)) {
            $fldName .= "-{$v[2]}";
          }

          $v3 = CRM_Utils_Array::value('3', $v);
          if ($v3 && trim($v3)) {
            $fldName .= "-{$v[3]}";
          }

          $value = $params['value'][$key][$k];
          if ($fldName == 'group' || $fldName == 'tag') {
            $value = trim($value);
            $value = str_replace('(', '', $value);
            $value = str_replace(')', '', $value);

            $v = explode(',', $value);
            $value = array();
            foreach ($v as $i) {
              $value[$i] = 1;
            }
          }

          if ($v[0] == 'Contribution' && substr($fldName, 0, 7) != 'custom_' 
            && substr($fldName, 0, 10) != 'financial_') {
            if (substr($fldName, 0, 13) != 'contribution_') {
              $fldName = 'contribution_' . $fldName;
            }
          }

          if ($row) {
            $fields[] = array(
              $fldName,
              $params['operator'][$key][$k],
              $value,
              $key,
              $k,
            );
          }
          else {
            $fields[] = array(
              $fldName,
              $params['operator'][$key][$k],
              $value,
              $key,
              0,
            );
          }
        }
      }
      if ($contactType) {
        $fields[] = array(
          'contact_type',
          '=',
          $contactType,
          $key,
          0,
        );
      }
    }

    //add sortByCharacter values
    if (isset($params['sortByCharacter'])) {
      $fields[] = array(
        'sortByCharacter',
        '=',
        $params['sortByCharacter'],
        0,
        0,
      );
    }


    return $fields;
  }

  static function &returnProperties(&$params) {
    $fields = array(
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
    );

    if (empty($params) || empty($params['mapper'])) {
      return $fields;
    }

    $locationTypes = CRM_Core_PseudoConstant::locationType();
    foreach ($params['mapper'] as $key => $value) {
      foreach ($value as $k => $v) {
        if (isset($v[1])) {
          if ($v[1] == 'groups' || $v[1] == 'tags') {
            continue;
          }

          if (isset($v[2]) && is_numeric($v[2])) {
            if (!array_key_exists('location', $fields)) {
              $fields['location'] = array();
            }

            // make sure that we have a location fields and a location type for this
            $locationName = $locationTypes[$v[2]];
            if (!array_key_exists($locationName, $fields['location'])) {
              $fields['location'][$locationName] = array();
              $fields['location'][$locationName]['location_type'] = $v[2];
            }

            if ($v[1] == 'phone' || $v[1] == 'email' || $v[1] == 'im') {
              // phone type handling
              if (isset($v[3])) {
                $fields['location'][$locationName][$v[1] . "-" . $v[3]] = 1;
              }
              else {
                $fields['location'][$locationName][$v[1]] = 1;
              }
            }
            else {
              $fields['location'][$locationName][$v[1]] = 1;
            }
          }
          else {
            $fields[$v[1]] = 1;
          }
        }
      }
    }

    return $fields;
  }

  /**
   * save the mapping field info for search builder / export given the formvalues
   *
   * @param array $params       asscociated array of formvalues
   * @param int   $mappingId    mapping id
   *
   * @return null
   * @static
   * @access public
   */
  static function saveMappingFields(&$params, $mappingId) {
    //delete mapping fields records for exixting mapping
    $mappingFields = new CRM_Core_DAO_MappingField();
    $mappingFields->mapping_id = $mappingId;
    $mappingFields->delete();

    if (empty($params['mapper'])) {
      return;
    }

    //save record in mapping field table
    foreach ($params['mapper'] as $key => $value) {
      $colCnt = 0;
      foreach ($value as $k => $v) {

        if (CRM_Utils_Array::value('1', $v)) {
          $saveMappingFields = new CRM_Core_DAO_MappingField();

          $saveMappingFields->mapping_id = $mappingId;
          $saveMappingFields->name = CRM_Utils_Array::value('1', $v);
          $saveMappingFields->contact_type = CRM_Utils_Array::value('0', $v);
          $locationId = CRM_Utils_Array::value('2', $v);
          $saveMappingFields->location_type_id = is_numeric($locationId) ? $locationId : NULL;

          if ($v[1] == 'phone') {
            $saveMappingFields->phone_type_id = CRM_Utils_Array::value('3', $v);
          }
          elseif ($v[1] == 'im') {
            $saveMappingFields->im_provider_id = CRM_Utils_Array::value('3', $v);
          }

          if (CRM_Utils_Array::value('operator', $params)) {
            $saveMappingFields->operator = CRM_Utils_Array::value($k, $params['operator'][$key]);            
          }
          if (CRM_Utils_Array::value('value', $params)) {
            $saveMappingFields->value = CRM_Utils_Array::value($k, $params['value'][$key]);
          }
          // Handle mapping for 'related contact' fields
          if (count(explode('_', CRM_Utils_Array::value('1', $v))) > 2) {
            list($id, $first, $second) = explode('_', CRM_Utils_Array::value('1', $v));
            if (($first == 'a' && $second == 'b') || ($first == 'b' && $second == 'a')) {

              if (CRM_Utils_Array::value('2', $v)) {
                $saveMappingFields->name = CRM_Utils_Array::value('2', $v);
              }
              elseif (CRM_Utils_Array::value('4', $v)) {
                $saveMappingFields->name = CRM_Utils_Array::value('4', $v);
              }

              if (is_numeric(CRM_Utils_Array::value('3', $v))) {
                $locationTypeid = CRM_Utils_Array::value('3', $v);
              }
              elseif (is_numeric(CRM_Utils_Array::value('5', $v))) {
                $locationTypeid = CRM_Utils_Array::value('5', $v);
              }

              if (is_numeric(CRM_Utils_Array::value('4', $v))) {
                $phoneTypeid = CRM_Utils_Array::value('4', $v);
              }
              elseif (is_numeric(CRM_Utils_Array::value('6', $v))) {
                $phoneTypeid = CRM_Utils_Array::value('6', $v);
              }

              $saveMappingFields->location_type_id = is_numeric($locationTypeid) ? $locationTypeid : NULL;
              $saveMappingFields->phone_type_id = is_numeric($phoneTypeid) ? $phoneTypeid : NULL;
              $saveMappingFields->relationship_type_id = $id;
              $saveMappingFields->relationship_direction = "{$first}_{$second}";
            }
          }

          $saveMappingFields->grouping = $key;
          $saveMappingFields->column_number = $colCnt;
          $saveMappingFields->save();
          $colCnt++;
          $locationTypeid = $phoneTypeid = NULL;
        }
      }
    }
  }
}

