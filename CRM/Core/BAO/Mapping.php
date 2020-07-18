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
class CRM_Core_BAO_Mapping extends CRM_Core_DAO_Mapping {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return object
   *   CRM_Core_DAO_Mapping object on success, otherwise NULL
   */
  public static function retrieve(&$params, &$defaults) {
    $mapping = new CRM_Core_DAO_Mapping();
    $mapping->copyValues($params);
    if ($mapping->find(TRUE)) {
      CRM_Core_DAO::storeValues($mapping, $defaults);
      return $mapping;
    }
    return NULL;
  }

  /**
   * Delete the mapping.
   *
   * @param int $id
   *   Mapping id.
   *
   * @return bool
   */
  public static function del($id) {
    // delete from mapping_field table
    $mappingField = new CRM_Core_DAO_MappingField();
    $mappingField->mapping_id = $id;
    $mappingField->delete();

    // delete from mapping table
    $mapping = new CRM_Core_DAO_Mapping();
    $mapping->id = $id;
    if ($mapping->find(TRUE)) {
      $result = $mapping->delete();
      return $result;
    }
    return FALSE;
  }

  /**
   * Takes an associative array and creates a contact object.
   *
   * The function extract all the params it needs to initialize the create a
   * contact object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   An array of name/value pairs.
   *
   * @return object
   *   CRM_Core_DAO_Mapper object on success, otherwise NULL
   */
  public static function add($params) {
    $mapping = new CRM_Core_DAO_Mapping();
    $mapping->copyValues($params);
    $mapping->save();

    return $mapping;
  }

  /**
   * Get the list of mappings for a select or select2 element.
   *
   * @param string $mappingType
   *   Mapping type name.
   * @param bool $select2
   *   Format for select2
   *
   * @return array
   *   Array of mapping names, keyed by id.
   */
  public static function getMappings($mappingType, $select2 = FALSE) {
    $result = civicrm_api3('Mapping', 'get', [
      'mapping_type_id' => $mappingType,
      'return' => ['name', 'description'],
      'options' => [
        'sort' => 'name',
        'limit' => 0,
      ],
    ]);
    $mapping = [];

    foreach ($result['values'] as $id => $value) {
      if ($select2) {
        $item = ['id' => $id, 'text' => $value['name']];
        if (!empty($value['description'])) {
          $item['description'] = $value['description'];
        }
        $mapping[] = $item;
      }
      else {
        $mapping[$id] = $value['name'];
      }
    }
    return $mapping;
  }

  /**
   * Get the mappings array, creating if it does not exist.
   *
   * @param string $mappingType
   *   Mapping type name.
   *
   * @return array
   *   Array of mapping names, keyed by id.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getCreateMappingValues($mappingType) {
    try {
      return CRM_Core_BAO_Mapping::getMappings($mappingType);
    }
    catch (CiviCRM_API3_Exception $e) {
      // Having a valid mapping_type_id is now enforced. However, rather than error let's
      // add it. This is required for Multi value which could be done by upgrade script, but
      // it feels like there could be other instances so this is safer.
      $errorParams = $e->getExtraParams();
      if ($errorParams['error_field'] === 'mapping_type_id') {
        $mappingValues = civicrm_api3('Mapping', 'getoptions', ['field' => 'mapping_type_id']);
        civicrm_api3('OptionValue', 'create', [
          'option_group_id' => 'mapping_type',
          'label' => $mappingType,
          'name' => $mappingType,
          'value' => max(array_keys($mappingValues['values'])) + 1,
          'is_reserved' => 1,
        ]);
        return CRM_Core_BAO_Mapping::getMappings($mappingType);
      }
      throw $e;
    }
  }

  /**
   * Get the mapping fields.
   *
   * @param int $mappingId
   *   Mapping id.
   *
   * @param bool $addPrimary
   *   Add the key 'Primary' when the field is a location field AND there is
   *   no location type (meaning Primary)?
   *
   * @return array
   *   array of mapping fields
   */
  public static function getMappingFields($mappingId, $addPrimary = FALSE) {
    //mapping is to be loaded from database
    $mapping = new CRM_Core_DAO_MappingField();
    $mapping->mapping_id = $mappingId;
    $mapping->orderBy('column_number');
    $mapping->find();

    $mappingName = $mappingLocation = $mappingContactType = $mappingPhoneType = [];
    $mappingImProvider = $mappingRelation = $mappingOperator = $mappingValue = $mappingWebsiteType = [];
    while ($mapping->fetch()) {
      $mappingName[$mapping->grouping][$mapping->column_number] = $mapping->name;
      $mappingContactType[$mapping->grouping][$mapping->column_number] = $mapping->contact_type;

      if (!empty($mapping->location_type_id)) {
        $mappingLocation[$mapping->grouping][$mapping->column_number] = $mapping->location_type_id;
      }
      elseif ($addPrimary) {
        if (CRM_Contact_BAO_Contact::isFieldHasLocationType($mapping->name)) {
          $mappingLocation[$mapping->grouping][$mapping->column_number] = ts('Primary');
        }
        else {
          $mappingLocation[$mapping->grouping][$mapping->column_number] = NULL;
        }
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

    return [
      $mappingName,
      $mappingContactType,
      $mappingLocation,
      $mappingPhoneType,
      $mappingImProvider,
      $mappingRelation,
      $mappingOperator,
      $mappingValue,
      $mappingWebsiteType,
    ];
  }

  /**
   * Get un-indexed array of the field values for the given mapping id.
   *
   * For example if passing a mapping ID & name the returned array would look like
   *   ['First field name', 'second field name']
   *
   * @param int $mappingID
   * @param string $fieldName
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function getMappingFieldValues($mappingID, $fieldName) {
    return array_merge(CRM_Utils_Array::collect($fieldName, civicrm_api3('MappingField', 'get', ['mapping_id' => $mappingID, 'return' => $fieldName])['values']));
  }

  /**
   * Check Duplicate Mapping Name.
   *
   * @param string $nameField
   *   mapping Name.
   * @param string $mapTypeId
   *   mapping Type.
   *
   * @return bool
   */
  public static function checkMapping($nameField, $mapTypeId) {
    $mapping = new CRM_Core_DAO_Mapping();
    $mapping->name = $nameField;
    $mapping->mapping_type_id = $mapTypeId;
    return (bool) $mapping->find(TRUE);
  }

  /**
   * Function returns associated array of elements, that will be passed for search.
   *
   * @param int $smartGroupId
   *   Smart group id.
   *
   * @return array
   *   associated array of elements
   */
  public static function getFormattedFields($smartGroupId) {
    $returnFields = [];

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
   * Build the mapping form for Search Builder.
   *
   * @param CRM_Core_Form $form
   * @param int $mappingId
   * @param int $columnNo
   * @param int $blockCount
   *   (no of blocks shown).
   * @param int $exportMode
   */
  public static function buildMappingForm(&$form, $mappingId, $columnNo, $blockCount, $exportMode = NULL) {

    $hasLocationTypes = [];
    $hasRelationTypes = [];

    $columnCount = $columnNo;
    $form->addElement('submit', 'addBlock', ts('Also include contacts where'),
      ['class' => 'submit-link']
    );

    $contactTypes = CRM_Contact_BAO_ContactType::basicTypes();
    $fields = self::getBasicFields('Search Builder');

    // Unset groups, tags, notes for component export
    if ($exportMode != CRM_Export_Form_Select::CONTACT_EXPORT) {
      foreach (array_keys($fields) as $type) {
        CRM_Utils_Array::remove($fields[$type], 'groups', 'tags', 'notes');
      }
    }

    // Build the common contact fields array.
    $fields['Contact'] = [];
    foreach ($fields[$contactTypes[0]] as $key => $value) {
      // If a field exists across all contact types, move it to the "Contact" selector
      $ubiquitious = TRUE;
      foreach ($contactTypes as $type) {
        if (!isset($fields[$type][$key])) {
          $ubiquitious = FALSE;
        }
      }
      if ($ubiquitious) {
        $fields['Contact'][$key] = $value;
        foreach ($contactTypes as $type) {
          unset($fields[$type][$key]);
        }
      }
    }
    if (array_key_exists('note', $fields['Contact'])) {
      $noteTitle = $fields['Contact']['note']['title'];
      $fields['Contact']['note']['title'] = $noteTitle . ': ' . ts('Body and Subject');
      $fields['Contact']['note_body'] = ['title' => $noteTitle . ': ' . ts('Body Only'), 'name' => 'note_body'];
      $fields['Contact']['note_subject'] = [
        'title' => $noteTitle . ': ' . ts('Subject Only'),
        'name' => 'note_subject',
      ];
    }

    // add component fields
    $compArray = self::addComponentFields($fields, 'Search Builder', $exportMode);

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

      if (isset($relatedMapperFields[$key]['related'])) {
        unset($relatedMapperFields[$key]['related']);
      }
    }

    $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');

    $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();

    // FIXME: dirty hack to make the default option show up first.  This
    // avoids a mozilla browser bug with defaults on dynamically constructed
    // selector widgets.
    if ($defaultLocationType) {
      $defaultLocation = $locationTypes[$defaultLocationType->id];
      unset($locationTypes[$defaultLocationType->id]);
      $locationTypes = [$defaultLocationType->id => $defaultLocation] + $locationTypes;
    }

    $locationTypes = [' ' => ts('Primary')] + $locationTypes;

    // since we need a hierarchical list to display contact types & subtypes,
    // this is what we going to display in first selector
    $contactTypeSelect = CRM_Contact_BAO_ContactType::getSelectElements(FALSE, FALSE);
    $contactTypeSelect = ['Contact' => ts('Contacts')] + $contactTypeSelect;

    $sel1 = ['' => ts('- select record type -')] + $contactTypeSelect + $compArray;

    foreach ($sel1 as $key => $sel) {
      if ($key) {
        // sort everything BUT the contactType which is sorted separately by
        // an initial commit of CRM-13278 (check ksort above)
        if (!in_array($key, $contactTypes)) {
          asort($mapperFields[$key]);
        }
        $sel2[$key] = ['' => ts('- select field -')] + $mapperFields[$key];
      }
    }

    $sel3[''] = NULL;
    $sel5[''] = NULL;
    $phoneTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $imProviders = CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id');
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

    // Array for core fields and relationship custom data
    $relationshipTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, NULL, TRUE);

    //special fields that have location, hack for primary location
    $specialFields = [
      'street_address',
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
      'city',
      'postal_code',
      'postal_code_suffix',
      'geo_code_1',
      'geo_code_2',
      'state_province',
      'country',
      'phone',
      'email',
      'im',
    ];

    if (isset($mappingId)) {
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

    $defaults = $noneArray = $nullArray = [];

    for ($x = 1; $x < $blockCount; $x++) {

      for ($i = 0; $i < $columnCount[$x]; $i++) {

        $sel = &$form->addElement('hierselect', "mapper[$x][$i]", ts('Mapper for Field %1', [1 => $i]), NULL);
        $jsSet = FALSE;

        if (isset($mappingId)) {
          list($mappingName, $defaults, $noneArray, $jsSet) = self::loadSavedMapping($mappingLocation, $x, $i, $mappingName, $mapperFields, $mappingContactType, $mappingRelation, $specialFields, $mappingPhoneType, $defaults, $noneArray, $mappingImProvider, $mappingOperator, $mappingValue);
        }
        //Fix for Search Builder
        $j = 4;

        $formValues = $form->exportValues();
        if (!$jsSet) {
          if (empty($formValues)) {
            // Incremented length for third select box(relationship type)
            for ($k = 1; $k < $j; $k++) {
              $noneArray[] = [$x, $i, $k];
            }
          }
          else {
            if (!empty($formValues['mapper'][$x])) {
              foreach ($formValues['mapper'][$x] as $value) {
                for ($k = 1; $k < $j; $k++) {
                  if (!isset($formValues['mapper'][$x][$i][$k]) ||
                    (!$formValues['mapper'][$x][$i][$k])
                  ) {
                    $noneArray[] = [$x, $i, $k];
                  }
                  else {
                    $nullArray[] = [$x, $i, $k];
                  }
                }
              }
            }
            else {
              for ($k = 1; $k < $j; $k++) {
                $noneArray[] = [$x, $i, $k];
              }
            }
          }
        }
        //Fix for Search Builder
        $sel->setOptions([$sel1, $sel2, $sel3, $sel4]);

        //CRM -2292, restricted array set
        $operatorArray = ['' => ts('-operator-')] + CRM_Core_SelectValues::getSearchBuilderOperators();

        $form->add('select', "operator[$x][$i]", '', $operatorArray);
        $form->add('text', "value[$x][$i]", '');
      }

      $form->addElement('submit', "addMore[$x]", ts('Another search field'), ['class' => 'submit-link']);
    }
    //end of block for

    $js = "<script type='text/javascript'>\n";
    $formName = "document.Builder";
    if (!empty($nullArray)) {
      $js .= "var nullArray = [";
      $elements = [];
      $seen = [];
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
                for (var i=0;i<nullArray.length;i++) {
                    if ( {$formName}['mapper['+nullArray[i][0]+']['+nullArray[i][1]+']['+nullArray[i][2]+']'] ) {
                        {$formName}['mapper['+nullArray[i][0]+']['+nullArray[i][1]+']['+nullArray[i][2]+']'].style.display = '';
                    }
                }
";
    }
    if (!empty($noneArray)) {
      $js .= "var noneArray = [";
      $elements = [];
      $seen = [];
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
                for (var i=0;i<noneArray.length;i++) {
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

  /**
   * @param string $mappingType
   * @return array
   */
  public static function getBasicFields($mappingType) {
    $contactTypes = CRM_Contact_BAO_ContactType::basicTypes();
    $fields = [];
    foreach ($contactTypes as $contactType) {
      if ($mappingType == 'Search Builder') {
        // Get multiple custom group fields in this context
        $contactFields = CRM_Contact_BAO_Contact::exportableFields($contactType, FALSE, FALSE, FALSE, TRUE);
      }
      else {
        $contactFields = CRM_Contact_BAO_Contact::exportableFields($contactType, FALSE, TRUE);
      }
      $contactFields = array_merge($contactFields, CRM_Contact_BAO_Query_Hook::singleton()->getFields());

      // Exclude the address options disabled in the Address Settings
      $fields[$contactType] = CRM_Core_BAO_Address::validateAddressOptions($contactFields);
      ksort($fields[$contactType]);
      if ($mappingType == 'Export') {
        $relationships = [];
        $relationshipTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, $contactType);
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
          $fields[$contactType] = array_merge($fields[$contactType],
            ['related' => ['title' => ts('- related contact info -')]],
            $relationships
          );
        }
      }
    }

    // Get the current employer for mapping.
    if ($mappingType == 'Export') {
      $fields['Individual']['current_employer_id']['title'] = ts('Current Employer ID');
    }

    // Contact Sub Type For export
    $subTypes = CRM_Contact_BAO_ContactType::subTypeInfo();
    foreach ($subTypes as $subType => $info) {
      //adding subtype specific relationships CRM-5256
      $csRelationships = [];

      if ($mappingType == 'Export') {
        $subTypeRelationshipTypes
          = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, $info['parent'],
          FALSE, 'label', TRUE, $subType);

        foreach ($subTypeRelationshipTypes as $key => $var) {
          if (!array_key_exists($key, $fields[$info['parent']])) {
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

      $fields[$subType] = $fields[$info['parent']] + $csRelationships;

      //custom fields for sub type
      $subTypeFields = CRM_Core_BAO_CustomField::getFieldsForImport($subType);
      $fields[$subType] += $subTypeFields;
    }

    return $fields;
  }

  /**
   * Adds component fields to the export fields array; returns list of components.
   *
   * @param array $fields
   * @param string $mappingType
   * @param int $exportMode
   * @return array
   */
  public static function addComponentFields(&$fields, $mappingType, $exportMode) {
    $compArray = [];

    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::CONTRIBUTE_EXPORT)) {
      if (CRM_Core_Permission::access('CiviContribute')) {
        $fields['Contribution'] = CRM_Core_DAO::getExportableFieldsWithPseudoConstants('CRM_Contribute_BAO_Contribution');
        unset($fields['Contribution']['contribution_contact_id']);
        $compArray['Contribution'] = ts('Contribution');
      }
    }

    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::EVENT_EXPORT)) {
      if (CRM_Core_Permission::access('CiviEvent')) {
        $fields['Participant'] = CRM_Event_BAO_Participant::exportableFields();
        //get the component payment fields
        // @todo - review this - inconsistent with other entities & hacky.
        if ($exportMode == CRM_Export_Form_Select::EVENT_EXPORT) {
          $componentPaymentFields = [];
          foreach ([
            'componentPaymentField_total_amount' => ts('Total Amount'),
            'componentPaymentField_contribution_status' => ts('Contribution Status'),
            'componentPaymentField_received_date' => ts('Date Received'),
            'componentPaymentField_payment_instrument' => ts('Payment Method'),
            'componentPaymentField_transaction_id' => ts('Transaction ID'),
          ] as $payField => $payTitle) {
            $componentPaymentFields[$payField] = ['title' => $payTitle];
          }
          $fields['Participant'] = array_merge($fields['Participant'], $componentPaymentFields);
        }

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
        if ($mappingType == 'Search Builder') {
          unset($fields['Grant']['grant_type_id']);
        }
        $compArray['Grant'] = ts('Grant');
      }
    }

    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::ACTIVITY_EXPORT)) {
      $fields['Activity'] = CRM_Activity_BAO_Activity::exportableFields('Activity');
      $compArray['Activity'] = ts('Activity');
    }

    return $compArray;
  }

  /**
   * Get the parameters for a mapping field in a saveable format from the quickform mapping format.
   *
   * @param array $defaults
   * @param array $v
   *
   * @return array
   */
  public static function getMappingParams($defaults, $v) {
    $locationTypeId = NULL;
    $saveMappingFields = $defaults;

    $saveMappingFields['name'] = $v['1'] ?? NULL;
    $saveMappingFields['contact_type'] = $v['0'] ?? NULL;
    $locationId = $v['2'] ?? NULL;
    $saveMappingFields['location_type_id'] = is_numeric($locationId) ? $locationId : NULL;

    if ($v[1] == 'phone') {
      $saveMappingFields['phone_type_id'] = $v['3'] ?? NULL;
    }
    elseif ($v[1] == 'im') {
      $saveMappingFields['im_provider_id'] = $v['3'] ?? NULL;
    }

    // Handle mapping for 'related contact' fields
    if (count(explode('_', CRM_Utils_Array::value('1', $v))) > 2) {
      list($id, $first, $second) = explode('_', CRM_Utils_Array::value('1', $v));
      if (($first == 'a' && $second == 'b') || ($first == 'b' && $second == 'a')) {

        if (!empty($v['2'])) {
          $saveMappingFields['name'] = $v['2'] ?? NULL;
        }
        elseif (!empty($v['4'])) {
          $saveMappingFields['name'] = $v['4'] ?? NULL;
        }

        if (is_numeric(CRM_Utils_Array::value('3', $v))) {
          $locationTypeId = $v['3'] ?? NULL;
        }
        elseif (is_numeric(CRM_Utils_Array::value('5', $v))) {
          $locationTypeId = $v['5'] ?? NULL;
        }

        if (is_numeric(CRM_Utils_Array::value('4', $v))) {
          if ($saveMappingFields['name'] === 'im') {
            $saveMappingFields['im_provider_id'] = $v[4];
          }
          else {
            $saveMappingFields['phone_type_id'] = $v['4'] ?? NULL;
          }
        }
        elseif (is_numeric(CRM_Utils_Array::value('6', $v))) {
          $saveMappingFields['phone_type_id'] = $v['6'] ?? NULL;
        }

        $saveMappingFields['location_type_id'] = is_numeric($locationTypeId) ? $locationTypeId : NULL;
        $saveMappingFields['relationship_type_id'] = $id;
        $saveMappingFields['relationship_direction'] = "{$first}_{$second}";
      }
    }

    return $saveMappingFields;
  }

  /**
   * Load saved mapping.
   *
   * @param $mappingLocation
   * @param int $x
   * @param int $i
   * @param $mappingName
   * @param $mapperFields
   * @param $mappingContactType
   * @param $mappingRelation
   * @param array $specialFields
   * @param $mappingPhoneType
   * @param $phoneType
   * @param array $defaults
   * @param array $noneArray
   * @param $imProvider
   * @param $mappingImProvider
   * @param $mappingOperator
   * @param $mappingValue
   *
   * @return array
   */
  protected static function loadSavedMapping($mappingLocation, int $x, int $i, $mappingName, $mapperFields, $mappingContactType, $mappingRelation, array $specialFields, $mappingPhoneType, array $defaults, array $noneArray, $mappingImProvider, $mappingOperator, $mappingValue) {
    $jsSet = FALSE;
    $locationId = $mappingLocation[$x][$i] ?? 0;
    if (isset($mappingName[$x][$i])) {
      if (is_array($mapperFields[$mappingContactType[$x][$i]])) {

        if (isset($mappingRelation[$x][$i])) {
          $relLocationId = $mappingLocation[$x][$i] ?? 0;
          if (!$relLocationId && in_array($mappingName[$x][$i], $specialFields)) {
            $relLocationId = " ";
          }

          $relPhoneType = $mappingPhoneType[$x][$i] ?? NULL;

          $defaults["mapper[$x][$i]"] = [
            $mappingContactType[$x][$i],
            $mappingRelation[$x][$i],
            $locationId,
            $phoneType,
            $mappingName[$x][$i],
            $relLocationId,
            $relPhoneType,
          ];

          if (!$locationId) {
            $noneArray[] = [$x, $i, 2];
          }
          if (!$phoneType && !$imProvider) {
            $noneArray[] = [$x, $i, 3];
          }
          if (!$mappingName[$x][$i]) {
            $noneArray[] = [$x, $i, 4];
          }
          if (!$relLocationId) {
            $noneArray[] = [$x, $i, 5];
          }
          if (!$relPhoneType) {
            $noneArray[] = [$x, $i, 6];
          }
          $noneArray[] = [$x, $i, 2];
        }
        else {
          $phoneType = $mappingPhoneType[$x][$i] ?? NULL;
          $imProvider = $mappingImProvider[$x][$i] ?? NULL;
          if (!$locationId && in_array($mappingName[$x][$i], $specialFields)) {
            $locationId = " ";
          }

          $defaults["mapper[$x][$i]"] = [
            $mappingContactType[$x][$i],
            $mappingName[$x][$i],
            $locationId,
            $phoneType,
          ];
          if (!$mappingName[$x][$i]) {
            $noneArray[] = [$x, $i, 1];
          }
          if (!$locationId) {
            $noneArray[] = [$x, $i, 2];
          }
          if (!$phoneType && !$imProvider) {
            $noneArray[] = [$x, $i, 3];
          }

          $noneArray[] = [$x, $i, 4];
          $noneArray[] = [$x, $i, 5];
          $noneArray[] = [$x, $i, 6];
        }

        $jsSet = TRUE;

        if (CRM_Utils_Array::value($i, CRM_Utils_Array::value($x, $mappingOperator))) {
          $defaults["operator[$x][$i]"] = $mappingOperator[$x][$i] ?? NULL;
        }

        if (CRM_Utils_Array::value($i, CRM_Utils_Array::value($x, $mappingValue))) {
          $defaults["value[$x][$i]"] = $mappingValue[$x][$i] ?? NULL;
        }
      }
    }
    return [$mappingName, $defaults, $noneArray, $jsSet];
  }

  /**
   * Function returns all custom fields with group title and
   * field label
   *
   * @param int $relationshipTypeId
   *   Related relationship type id.
   *
   * @return array
   *   all custom field titles
   */
  public function getRelationTypeCustomGroupData($relationshipTypeId) {

    $customFields = CRM_Core_BAO_CustomField::getFields('Relationship', NULL, NULL, $relationshipTypeId, NULL, NULL);
    $groupTitle = [];
    foreach ($customFields as $krelation => $vrelation) {
      $groupTitle[$vrelation['label']] = $vrelation['groupTitle'] . '...' . $vrelation['label'];
    }
    return $groupTitle;
  }

  /**
   * Function returns all  Custom group Names.
   *
   * @param int $customfieldId
   *   Related file id.
   *
   * @return null|string
   *   $customGroupName all custom group names
   */
  public static function getCustomGroupName($customfieldId) {
    if ($customFieldId = CRM_Core_BAO_CustomField::getKeyID($customfieldId)) {
      $customGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $customFieldId, 'custom_group_id');
      $customGroupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'title');

      $customGroupName = CRM_Utils_String::ellipsify($customGroupName, 13);

      return $customGroupName;
    }
  }

  /**
   * Function returns associated array of elements, that will be passed for search
   *
   * @param array $params
   *   Associated array of submitted values.
   * @param bool $row
   *   Row no of the fields.
   *
   *
   * @return array
   *   formatted associated array of elements
   * @throws CRM_Core_Exception
   */
  public static function formattedFields(&$params, $row = FALSE) {
    $fields = [];

    if (empty($params) || !isset($params['mapper'])) {
      return $fields;
    }

    $types = ['Individual', 'Organization', 'Household'];
    foreach ($params['mapper'] as $key => $value) {
      $contactType = NULL;
      foreach ($value as $k => $v) {
        if (in_array($v[0], $types)) {
          if ($contactType && $contactType != $v[0]) {
            throw new CRM_Core_Exception(ts("Cannot have two clauses with different types: %1, %2",
              [1 => $contactType, 2 => $v[0]]
            ));
          }
          $contactType = $v[0];
        }
        if (!empty($v['1'])) {
          $fldName = $v[1];
          $v2 = $v['2'] ?? NULL;
          if ($v2 && trim($v2)) {
            $fldName .= "-{$v[2]}";
          }

          $v3 = $v['3'] ?? NULL;
          if ($v3 && trim($v3)) {
            $fldName .= "-{$v[3]}";
          }

          $value = $params['value'][$key][$k];

          if ($v[0] == 'Contribution' && substr($fldName, 0, 7) != 'custom_'
            && substr($fldName, 0, 10) != 'financial_'
            && substr($fldName, 0, 8) != 'payment_') {
            if (substr($fldName, 0, 13) != 'contribution_') {
              $fldName = 'contribution_' . $fldName;
            }
          }

          // CRM-14983: verify if values are comma separated convert to array
          if (!is_array($value) && strstr($params['operator'][$key][$k], 'IN')) {
            $value = explode(',', $value);
            $value = [$params['operator'][$key][$k] => $value];
          }
          // CRM-19081 Fix legacy StateProvince Field Values.
          // These derive from smart groups created using search builder under older
          // CiviCRM versions.
          if (!is_numeric($value) && $fldName == 'state_province') {
            $value = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'state_province_id', $value);
          }

          if ($row) {
            $fields[] = [
              $fldName,
              $params['operator'][$key][$k],
              $value,
              $key,
              $k,
            ];
          }
          else {
            $fields[] = [
              $fldName,
              $params['operator'][$key][$k],
              $value,
              $key,
              0,
            ];
          }
        }
      }
      if ($contactType) {
        $fields[] = [
          'contact_type',
          '=',
          $contactType,
          $key,
          0,
        ];
      }
    }

    //add sortByCharacter values
    if (isset($params['sortByCharacter'])) {
      $fields[] = [
        'sortByCharacter',
        '=',
        $params['sortByCharacter'],
        0,
        0,
      ];
    }
    return $fields;
  }

  /**
   * @param array $params
   *
   * @return array
   */
  public static function &returnProperties(&$params) {
    $fields = [
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
    ];

    if (empty($params) || empty($params['mapper'])) {
      return $fields;
    }

    $locationTypes = CRM_Core_DAO_Address::buildOptions('location_type_id', 'validate');
    foreach ($params['mapper'] as $key => $value) {
      foreach ($value as $k => $v) {
        if (isset($v[1])) {
          if ($v[1] == 'groups' || $v[1] == 'tags') {
            continue;
          }

          if (isset($v[2]) && is_numeric($v[2])) {
            if (!array_key_exists('location', $fields)) {
              $fields['location'] = [];
            }

            // make sure that we have a location fields and a location type for this
            $locationName = $locationTypes[$v[2]];
            if (!array_key_exists($locationName, $fields['location'])) {
              $fields['location'][$locationName] = [];
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
   * Save the mapping field info for search builder / export given the formvalues
   *
   * @param array $params
   *   Asscociated array of formvalues.
   * @param int $mappingId
   *   Mapping id.
   *
   * @return NULL
   */
  public static function saveMappingFields($params, $mappingId) {
    //delete mapping fields records for existing mapping
    $mappingFields = new CRM_Core_DAO_MappingField();
    $mappingFields->mapping_id = $mappingId;
    $mappingFields->delete();

    if (empty($params['mapper'])) {
      return NULL;
    }

    //save record in mapping field table
    foreach ($params['mapper'] as $key => $value) {
      $colCnt = 0;
      foreach ($value as $k => $v) {

        if (!empty($v['1'])) {
          $saveMappingParams = self::getMappingParams(
          [
            'mapping_id' => $mappingId,
            'grouping' => $key,
            'operator' => $params['operator'][$key][$k] ?? NULL,
            'value' => $params['value'][$key][$k] ?? NULL,
            'column_number' => $colCnt,
          ], $v);
          $saveMappingField = new CRM_Core_DAO_MappingField();
          $saveMappingField->copyValues($saveMappingParams);
          $saveMappingField->save();
          $colCnt++;
        }
      }
    }
  }

}
