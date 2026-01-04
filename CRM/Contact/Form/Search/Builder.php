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
 * This class is for search builder processing.
 */
class CRM_Contact_Form_Search_Builder extends CRM_Contact_Form_Search {

  /**
   * Number of columns in where.
   *
   * @var int
   */
  public $_columnCount;

  /**
   * Number of blocks to be shown.
   *
   * @var int
   */
  public $_blockCount;

  /**
   * Build the form object.
   */
  public function preProcess() {
    // SearchFormName is deprecated & to be removed - the replacement is for the task to
    // call $this->form->getSearchFormValues()
    // A couple of extensions use it.
    $this->set('searchFormName', 'Builder');

    $this->set('context', 'builder');
    parent::preProcess();

    // Get the block count
    $this->_blockCount = $this->get('blockCount');
    // Initialize new form
    if (!$this->_blockCount) {
      $this->_blockCount = 4;
      if (!$this->_ssID) {
        $this->set('newBlock', 1);
      }
    }

    //get the column count
    $this->_columnCount = $this->get('columnCount');

    for ($i = 1; $i < $this->_blockCount; $i++) {
      if (empty($this->_columnCount[$i])) {
        $this->_columnCount[$i] = 5;
      }
    }

    if ($this->get('showSearchForm')) {
      $this->assign('showSearchForm', TRUE);
    }
    else {
      $this->assign('showSearchForm', FALSE);
    }
  }

  /**
   * Build quick form.
   */
  public function buildQuickForm() {
    $fields = self::fields();
    $searchByLabelFields = [];
    // This array contain list of available fields and their corresponding data type,
    //  later assigned as json string, to be used to filter list of mysql operators
    $fieldNameTypes = [];
    foreach ($fields as $name => $field) {
      // Assign date type to respective field name, which will be later used to modify operator list
      $fieldNameTypes[$name] = CRM_Utils_Type::typeToString($field['type'] ?? NULL);
      // it's necessary to know which of the fields are searchable by label
      if (isset($field['searchByLabel']) && $field['searchByLabel']) {
        $searchByLabelFields[] = $name;
      }
    }
    [$fieldOptions, $fkEntities] = self::fieldOptions();
    // Add javascript
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/Contact/Form/Search/Builder.js', 1, 'html-header')
      ->addSetting([
        'searchBuilder' => [
          // Index of newly added/expanded block (1-based index)
          'newBlock' => $this->get('newBlock'),
          'fieldOptions' => $fieldOptions,
          'fkEntities' => $fkEntities,
          'searchByLabelFields' => $searchByLabelFields,
          'fieldTypes' => $fieldNameTypes,
          'generalOperators' => ['' => ts('-operator-')] + CRM_Core_SelectValues::getSearchBuilderOperators(),
        ],
      ]);
    //get the saved search mapping id
    $mappingId = NULL;
    if ($this->_ssID) {
      $mappingId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $this->_ssID, 'mapping_id');
    }

    $this->buildMappingForm($this, $mappingId, $this->_columnCount, $this->_blockCount);

    parent::buildQuickForm();
  }

  /**
   * Add local and global form rules.
   */
  public function addRules() {
    $this->addFormRule(['CRM_Contact_Form_Search_Builder', 'formRule'], $this);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   * @param array $files
   * @param CRM_Core_Form $self
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values, $files, $self) {
    if (!empty($values['addMore']) || !empty($values['addBlock'])) {
      return TRUE;
    }
    $fields = self::fields();
    $fld = CRM_Core_BAO_Mapping::formattedFields($values, TRUE);

    $errorMsg = [];
    foreach ($fld as $k => $v) {
      if (!$v[1]) {
        $errorMsg["operator[$v[3]][$v[4]]"] = ts("Please enter the operator.");
      }
      else {
        // CRM-10338
        $v[2] = self::checkArrayKeyEmpty($v[2]);

        if (in_array($v[1], [
          'IS NULL',
          'IS NOT NULL',
          'IS EMPTY',
          'IS NOT EMPTY',
        ]) && !empty($v[2])) {
          $errorMsg["value[$v[3]][$v[4]]"] = ts('Please clear your value if you want to use %1 operator.', [1 => $v[1]]);
        }
        elseif (substr($v[0], 0, 7) === 'do_not_' or substr($v[0], 0, 3) === 'is_') {
          if (isset($v[2])) {
            $v2 = [$v[2]];
            if (!isset($v[2])) {
              $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a value.");
            }

            $error = CRM_Utils_Type::validate($v2[0], 'Integer', FALSE);
            if ($error != $v2[0]) {
              $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a valid value.");
            }
          }
          else {
            $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a value.");
          }
        }
        else {
          if (substr($v[0], 0, 7) == 'custom_') {
            // Get rid of appended location type id
            [$fieldKey] = explode('-', $v[0]);
            $type = $fields[$fieldKey]['data_type'];

            // hack to handle custom data of type state and country
            if (in_array($type, [
              'Country',
              'StateProvince',
            ])) {
              $type = "Integer";
            }
          }
          else {
            $fldName = $v[0];
            // FIXME: no idea at this point what to do with this,
            // FIXME: but definitely needs fixing.
            if (substr($v[0], 0, 13) == 'contribution_') {
              $fldName = substr($v[0], 13);
            }

            $fldValue = $fields[$fldName] ?? NULL;
            $fldType = $fldValue['type'] ?? NULL;
            $type = CRM_Utils_Type::typeToString($fldType);

            if (str_contains($v[1], 'IN')) {
              if (empty($v[2])) {
                $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a value.");
              }
            }
            // Check Empty values for Integer Or Boolean Or Date type For operators other than IS NULL and IS NOT NULL.
            elseif (!in_array($v[1],
              ['IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY'])
            ) {
              if ((($type == 'Int' || $type == 'Boolean') && !is_array($v[2]) && !trim($v[2])) && $v[2] != '0') {
                $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a value.");
              }
              elseif ($type == 'Date' && !trim($v[2])) {
                $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a value.");
              }
            }
          }

          if ($type && empty($errorMsg)) {
            // check for valid format while using IN Operator
            if (str_contains($v[1], 'IN')) {
              if (!is_array($v[2])) {
                $inVal = trim($v[2]);
                //checking for format to avoid db errors
                if ($type == 'Int') {
                  if (!preg_match('/^[A-Za-z0-9\,]+$/', $inVal)) {
                    $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter correct Data (in valid format).");
                  }
                }
                else {
                  if (!preg_match('/^[A-Za-z0-9åäöÅÄÖüÜœŒæÆøØ()\,\s]+$/', $inVal)) {
                    $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter correct Data (in valid format).");
                  }
                }
              }

              // Validate each value in parenthesis to avoid db errors
              if (empty($errorMsg)) {
                $parenValues = [];
                $parenValues = is_array($v[2]) ? (array_key_exists($v[1], $v[2])) ? $v[2][$v[1]] : $v[2] : explode(',', trim($inVal, "(..)"));
                foreach ($parenValues as $val) {
                  if ($type == 'Date' || $type == 'Timestamp') {
                    $val = CRM_Utils_Date::processDate($val);
                    if ($type == 'Date') {
                      $val = substr($val, 0, 8);
                    }
                  }
                  else {
                    $val = trim($val);
                  }
                  if (!$val && $val != '0') {
                    $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter the values correctly.");
                  }
                  if (empty($errorMsg)) {
                    $error = CRM_Utils_Type::validate($val, $type, FALSE);
                    if ($error != $val) {
                      $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a valid value.");
                    }
                  }
                }
              }
            }
            elseif (trim($v[2])) {
              //else check value for rest of the Operators
              if ($type == 'Date' || $type == 'Timestamp') {
                $v[2] = CRM_Utils_Date::processDate($v[2]);
                if ($type == 'Date') {
                  $v[2] = substr($v[2], 0, 8);
                }
              }
              $error = CRM_Utils_Type::validate($v[2], $type, FALSE);
              if ($error != $v[2]) {
                $errorMsg["value[$v[3]][$v[4]]"] = ts("Please enter a valid value.");
              }
            }
          }
        }
      }
    }

    if (!empty($errorMsg)) {
      $self->set('showSearchForm', TRUE);
      $self->assign('rows', NULL);
      return $errorMsg;
    }

    return TRUE;
  }

  /**
   * Normalise form values.
   */
  public function normalizeFormValues() {
  }

  /**
   * Convert form values.
   *
   * @param array $formValues
   *
   * @return array
   */
  public function convertFormValues(&$formValues) {
    return CRM_Core_BAO_Mapping::formattedFields($formValues);
  }

  /**
   * Get return properties.
   *
   * @return array
   */
  public function &returnProperties() {
    return CRM_Core_BAO_Mapping::returnProperties($this->_formValues);
  }

  /**
   * Process the uploaded file.
   */
  public function postProcess() {
    $this->set('isAdvanced', '2');
    $this->set('isSearchBuilder', '1');
    $this->set('showSearchForm', FALSE);

    $params = $this->controller->exportValues($this->_name);
    if (!empty($params)) {
      // Add another block
      if (!empty($params['addBlock'])) {
        $this->set('newBlock', $this->_blockCount);
        $this->_blockCount += 3;
        $this->set('blockCount', $this->_blockCount);
        $this->set('showSearchForm', TRUE);
        return;
      }
      // Add another field
      $addMore = $params['addMore'] ?? NULL;
      for ($x = 1; $x <= $this->_blockCount; $x++) {
        if (!empty($addMore[$x])) {
          $this->set('newBlock', $x);
          $this->_columnCount[$x] = $this->_columnCount[$x] + 5;
          $this->set('columnCount', $this->_columnCount);
          $this->set('showSearchForm', TRUE);
          return;
        }
      }
      $this->set('newBlock', NULL);
      $checkEmpty = NULL;
      foreach ($params['mapper'] as $key => $value) {
        foreach ($value as $k => $v) {
          if ($v[0]) {
            $checkEmpty++;
          }
        }
      }

      if (!$checkEmpty) {
        $this->set('newBlock', 1);
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/search/builder', '_qf_Builder_display=true'));
      }
    }

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
      $this->set('uf_group_id', $this->_formValues['uf_group_id'] ?? '');
    }

    // we dont want to store the sortByCharacter in the formValue, it is more like
    // a filter on the result set
    // this filter is reset if we click on the search button
    if ($this->_sortByCharacter !== NULL && empty($_POST)) {
      if (strtolower($this->_sortByCharacter) == 'all') {
        $this->_formValues['sortByCharacter'] = NULL;
      }
      else {
        $this->_formValues['sortByCharacter'] = $this->_sortByCharacter;
      }
    }
    else {
      $this->_sortByCharacter = NULL;
    }

    $this->_params = $this->convertFormValues($this->_formValues);
    $this->_returnProperties = &$this->returnProperties();

    // CRM-10338 check if value is empty array
    foreach ($this->_params as $k => $v) {
      $this->_params[$k][2] = self::checkArrayKeyEmpty($v[2]);
    }

    parent::postProcess();
  }

  /**
   * Get fields.
   *
   * @return array
   */
  public static function fields() {
    $fields = array_merge(
      CRM_Contact_BAO_Contact::exportableFields('All', FALSE, TRUE),
      CRM_Core_Component::getQueryFields(),
      CRM_Contact_BAO_Query_Hook::singleton()->getFields(),
      CRM_Activity_BAO_Activity::exportableFields()
    );
    return $fields;
  }

  /**
   * CRM-9434 Hackish function to fetch fields with options.
   *
   * FIXME: When our core fields contain reliable metadata this will be much simpler.
   * @return array
   *   (string => string) key: field_name value: api entity name
   *   Note: options are fetched via ajax using the api "getoptions" method
   */
  public static function fieldOptions() {
    // Hack to add options not retrieved by getfields
    // This list could go on and on, but it would be better to fix getfields
    $options = [
      'group' => 'group_contact',
      'tag' => 'entity_tag',
      'on_hold' => 'yesno',
      'is_bulkmail' => 'yesno',
      'payment_instrument' => 'contribution',
      'membership_status' => 'membership',
      'membership_type' => 'membership',
      'member_campaign_id' => 'membership',
      'member_is_test' => 'yesno',
      'member_is_pay_later' => 'yesno',
      'is_override' => 'yesno',
    ];
    $entities = [
      'contact',
      'address',
      'activity',
      'participant',
      'pledge',
      'member',
      'contribution',
      'case',
    ];
    CRM_Contact_BAO_Query_Hook::singleton()->alterSearchBuilderOptions($entities, $options);
    $fkEntities = [];
    foreach ($entities as $entity) {
      $fields = civicrm_api3($entity, 'getfields');
      foreach ($fields['values'] as $field => $info) {
        if (!empty($info['options']) || !empty($info['pseudoconstant']) || !empty($info['option_group_id'])) {
          $options[$field] = $entity;
          // Hack for when search field doesn't match db field - e.g. "country" instead of "country_id"
          if (substr($field, -3) == '_id') {
            $options[substr($field, 0, -3)] = $entity;
          }
        }
        elseif (!empty($info['data_type'])) {
          if (in_array($info['data_type'], ['StateProvince', 'Country'])) {
            $options[$field] = $entity;
          }
        }
        elseif (!empty($info['FKApiName'])) {
          $fkEntities[$field] = $info['FKApiName'];
        }
        elseif (in_array(substr($field, 0, 3), [
          'is_',
          'do_',
        ]) || ($info['data_type'] ?? NULL) == 'Boolean'
        ) {
          $options[$field] = 'yesno';
          if ($entity != 'contact') {
            $options[$entity . '_' . $field] = 'yesno';
          }
        }
        elseif (strpos($field, '_is_')) {
          $options[$field] = 'yesno';
        }
      }
    }
    return [$options, $fkEntities];
  }

  /**
   * CRM-10338 tags and groups use array keys for selection list.
   *
   * if using IS NULL/NOT NULL, an array with no array key is created
   * convert that to simple NULL so processing can proceed
   *
   * @param string $val
   *
   * @return null
   */
  public static function checkArrayKeyEmpty($val) {
    if (is_array($val)) {
      $v2empty = TRUE;
      foreach ($val as $vk => $vv) {
        if (!empty($vk)) {
          $v2empty = FALSE;
        }
      }
      if ($v2empty) {
        $val = NULL;
      }
    }
    return $val;
  }

  /**
   * Build the mapping form for Search Builder.
   *
   * @param CRM_Core_Form $form
   * @param int $mappingId
   * @param int $columnNo
   * @param int $blockCount
   *   (no of blocks shown).
   */
  private function buildMappingForm(&$form, $mappingId, $columnNo, $blockCount) {

    $hasLocationTypes = [];
    $hasRelationTypes = [];

    $columnCount = $columnNo;
    $form->addElement('xbutton', 'addBlock', ts('Also include contacts where'),
      [
        'type' => 'submit',
        'class' => 'submit-link',
        'value' => 1,
      ]
    );

    $contactTypes = CRM_Contact_BAO_ContactType::basicTypes();
    $fields = CRM_Core_BAO_Mapping::getBasicFields('Search Builder');

    // Unset groups, tags, notes for component export
    foreach (array_keys($fields) as $type) {
      CRM_Utils_Array::remove($fields[$type], 'groups', 'tags', 'notes');
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
    $compArray = CRM_Core_BAO_Mapping::addComponentFields($fields, 'Search Builder', NULL);

    foreach ($fields as $key => $value) {

      foreach ($value as $key1 => $value1) {
        //CRM-2676, replacing the conflict for same custom field name from different custom group.
        $customFieldId = CRM_Core_BAO_CustomField::getKeyID($key1);

        if ($customFieldId) {
          $customGroupName = CRM_Core_BAO_CustomField::getField($customFieldId)['custom_group']['title'];
          $customGroupName = CRM_Utils_String::ellipsify($customGroupName, 13);
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

    $locationTypes = CRM_Core_DAO_Address::buildOptions('location_type_id');

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
    $phoneTypes = CRM_Core_DAO_Phone::buildOptions('phone_type_id');
    $imProviders = CRM_Core_DAO_IM::buildOptions('provider_id');
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
      [$mappingName, $mappingContactType, $mappingLocation, $mappingPhoneType, $mappingImProvider, $mappingRelation, $mappingOperator, $mappingValue] = $this->getMappingFields($mappingId);

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
          [$mappingName, $defaults, $noneArray, $jsSet] = $this->loadSavedMapping($mappingLocation, $x, $i, $mappingName, $mapperFields, $mappingContactType, $mappingRelation, $specialFields, $mappingPhoneType, $defaults, $noneArray, $mappingImProvider, $mappingOperator, $mappingValue);
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

      $form->addElement('xbutton', "addMore[$x]", ts('Another search field'), [
        'type' => 'submit',
        'class' => 'submit-link',
        'value' => 1,
      ]);
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
  protected function loadSavedMapping($mappingLocation, int $x, int $i, $mappingName, $mapperFields, $mappingContactType, $mappingRelation, array $specialFields, $mappingPhoneType, array $defaults, array $noneArray, $mappingImProvider, $mappingOperator, $mappingValue) {
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

        if (!empty($mappingOperator[$x][$i])) {
          $defaults["operator[$x][$i]"] = $mappingOperator[$x][$i];
        }

        if (isset($mappingValue[$x][$i])) {
          $defaults["value[$x][$i]"] = $mappingValue[$x][$i];
        }
      }
    }
    return [$mappingName, $defaults, $noneArray, $jsSet];
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
  private function getMappingFields($mappingId, $addPrimary = FALSE) {
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

      if (isset($mapping->value)) {
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

}
