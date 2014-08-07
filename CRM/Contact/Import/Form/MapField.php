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
 * This class gets the name of the file to upload
 */
class CRM_Contact_Import_Form_MapField extends CRM_Import_Form_MapField {


  /**
   * an array of all contact fields with
   * formatted custom field names.
   *
   * @var array
   * @access protected
   */
  protected $_formattedFieldNames;

  /**
   * on duplicate
   *
   * @var int
   */
  public $_onDuplicate;

  protected $_dedupeFields;

  protected static $customFields;

  /**
   * Attempt to match header labels with our mapper fields
   * FIXME: This is essentially the same function as parent::defaultFromHeader
   *
   * @param header
   * @param mapperFields
   *
   * @return string
   * @access public
   */
  public function defaultFromColumnName($columnName, &$patterns) {

    if (!preg_match('/^[a-z0-9 ]$/i', $columnName)) {
      if ($columnKey = array_search($columnName, $this->_mapperFields)) {
        $this->_fieldUsed[$columnKey] = TRUE;
        return $columnKey;
      }
    }

    foreach ($patterns as $key => $re) {
      // Skip empty key/patterns
      if (!$key || !$re || strlen("$re") < 5) {
        continue;
      }

      if (preg_match($re, $columnName)) {
        $this->_fieldUsed[$key] = TRUE;
        return $key;
      }
    }
    return '';
  }

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $dataSource = $this->get('dataSource');
    $skipColumnHeader = $this->get('skipColumnHeader');
    $this->_mapperFields = $this->get('fields');
    $this->_importTableName = $this->get('importTableName');
    $this->_onDuplicate = $this->get('onDuplicate');
    $highlightedFields = array();
    $highlightedFields[] = 'email';
    $highlightedFields[] = 'external_identifier';
    //format custom field names, CRM-2676
    switch ($this->get('contactType')) {
      case CRM_Import_Parser::CONTACT_INDIVIDUAL:
        $contactType         = 'Individual';
        $highlightedFields[] = 'first_name';
        $highlightedFields[] = 'last_name';
        break;

      case CRM_Import_Parser::CONTACT_HOUSEHOLD:
        $contactType = 'Household';
        $highlightedFields[] = 'household_name';
        break;

      case CRM_Import_Parser::CONTACT_ORGANIZATION:
        $contactType = 'Organization';
        $highlightedFields[] = 'organization_name';
        break;
    }
    $this->_contactType = $contactType;
    if ($this->_onDuplicate == CRM_Import_Parser::DUPLICATE_SKIP) {
      unset($this->_mapperFields['id']);
    }
    else {
      $highlightedFields[] = 'id';
    }

    if ($this->_onDuplicate != CRM_Import_Parser::DUPLICATE_NOCHECK) {
      //Mark Dedupe Rule Fields as required, since it's used in matching contact
      foreach (array(
        'Individual', 'Household', 'Organization') as $cType) {
        $ruleParams = array(
          'contact_type' => $cType,
          'used'         => 'Unsupervised',
        );
        $this->_dedupeFields[$cType] = CRM_Dedupe_BAO_Rule::dedupeRuleFields($ruleParams);
      }

      //Modify mapper fields title if fields are present in dedupe rule
      if (is_array($this->_dedupeFields[$contactType])) {
        foreach ($this->_dedupeFields[$contactType] as $val) {
          if ($valTitle = CRM_Utils_Array::value($val, $this->_mapperFields)) {
            $this->_mapperFields[$val] = $valTitle . ' (match to contact)';
          }
        }
      }
    }
    // retrieve and highlight required custom fields
    $formattedFieldNames = $this->formatCustomFieldName($this->_mapperFields);
    self::$customFields = CRM_Core_BAO_CustomField::getFields($this->_contactType);
    foreach(self::$customFields as $key => $attr) {
      if (!empty($attr['is_required'])) {
        $highlightedFields[] = "custom_$key";
      }
    }
    $this->assign('highlightedFields', $highlightedFields);
    $this->_formattedFieldNames[$contactType] = $this->_mapperFields = array_merge($this->_mapperFields, $formattedFieldNames);

    $columnNames = array();
    //get original col headers from csv if present.
    if ($dataSource == 'CRM_Import_DataSource_CSV' && $skipColumnHeader) {
      $columnNames = $this->get('originalColHeader');
    }
    else {
      // get the field names from the temp. DB table
      $dao = new CRM_Core_DAO();
      $db = $dao->getDatabaseConnection();

      $columnsQuery = "SHOW FIELDS FROM $this->_importTableName
                         WHERE Field NOT LIKE '\_%'";
      $columnsResult = $db->query($columnsQuery);
      while ($row = $columnsResult->fetchRow(DB_FETCHMODE_ASSOC)) {
        $columnNames[] = $row['Field'];
      }
    }

    $showColNames = TRUE;
    if ($dataSource == 'CRM_Import_DataSource_CSV' && !$skipColumnHeader) {
      $showColNames = FALSE;
    }
    $this->assign('showColNames', $showColNames);

    $this->_columnCount = count($columnNames);
    $this->_columnNames = $columnNames;
    $this->assign('columnNames', $columnNames);
    //$this->_columnCount = $this->get( 'columnCount' );
    $this->assign('columnCount', $this->_columnCount);
    $this->_dataValues = $this->get('dataValues');
    $this->assign('dataValues', $this->_dataValues);
    $this->assign('rowDisplayCount', 2);
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    //to save the current mappings
    if (!$this->get('savedMapping')) {
      $saveDetailsName = ts('Save this field mapping');
      $this->applyFilter('saveMappingName', 'trim');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
    }
    else {
      $savedMapping = $this->get('savedMapping');

      list($mappingName, $mappingContactType, $mappingLocation, $mappingPhoneType, $mappingImProvider, $mappingRelation, $mappingOperator, $mappingValue, $mappingWebsiteType) = CRM_Core_BAO_Mapping::getMappingFields($savedMapping);

      //get loaded Mapping Fields
      $mappingName        = CRM_Utils_Array::value(1, $mappingName);
      $mappingContactType = CRM_Utils_Array::value(1, $mappingContactType);
      $mappingLocation    = CRM_Utils_Array::value(1, $mappingLocation);
      $mappingPhoneType   = CRM_Utils_Array::value(1, $mappingPhoneType);
      $mappingImProvider  = CRM_Utils_Array::value(1, $mappingImProvider);
      $mappingRelation    = CRM_Utils_Array::value(1, $mappingRelation);
      $mappingWebsiteType = CRM_Utils_Array::value(1, $mappingWebsiteType);

      $this->assign('loadedMapping', $savedMapping);
      $this->set('loadedMapping', $savedMapping);

      $params         = array('id' => $savedMapping);
      $temp           = array();
      $mappingDetails = CRM_Core_BAO_Mapping::retrieve($params, $temp);

      $this->assign('savedName', $mappingDetails->name);

      $this->add('hidden', 'mappingId', $savedMapping);

      $this->addElement('checkbox', 'updateMapping', ts('Update this field mapping'), NULL);
      $saveDetailsName = ts('Save as a new field mapping');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
    }

    $this->addElement('checkbox', 'saveMapping', $saveDetailsName, NULL, array('onclick' => "showSaveDetails(this)"));

    $this->addFormRule(array('CRM_Contact_Import_Form_MapField', 'formRule'));

    //-------- end of saved mapping stuff ---------

    $defaults         = array();
    $mapperKeys       = array_keys($this->_mapperFields);
    $hasColumnNames   = !empty($this->_columnNames);
    $columnPatterns   = $this->get('columnPatterns');
    $dataPatterns     = $this->get('dataPatterns');
    $hasLocationTypes = $this->get('fieldTypes');

    $this->_location_types = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');

    $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();

    // Pass default location to js
    if ($defaultLocationType) {
      $this->assign('defaultLocationType', $defaultLocationType->id);
      $this->assign('defaultLocationTypeLabel', $this->_location_types[$defaultLocationType->id]);
    }

    /* Initialize all field usages to false */
    foreach ($mapperKeys as $key) {
      $this->_fieldUsed[$key] = FALSE;
    }

    $sel1 = $this->_mapperFields;
    $sel2[''] = NULL;

    $phoneTypes   = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $imProviders  = CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id');
    $websiteTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Website', 'website_type_id');

    foreach ($this->_location_types as $key => $value) {
      $sel3['phone'][$key] = &$phoneTypes;
      //build array for IM service provider type for contact
      $sel3['im'][$key] = &$imProviders;
    }

    $sel4 = NULL;

    // store and cache all relationship types
    $contactRelation = new CRM_Contact_DAO_RelationshipType();
    $contactRelation->find();
    while ($contactRelation->fetch()) {
      $contactRelationCache[$contactRelation->id] = array();
      $contactRelationCache[$contactRelation->id]['contact_type_a'] = $contactRelation->contact_type_a;
      $contactRelationCache[$contactRelation->id]['contact_sub_type_a'] = $contactRelation->contact_sub_type_a;
      $contactRelationCache[$contactRelation->id]['contact_type_b'] = $contactRelation->contact_type_b;
      $contactRelationCache[$contactRelation->id]['contact_sub_type_b'] = $contactRelation->contact_sub_type_b;
    }
    $highlightedFields = $highlightedRelFields = array();

    $highlightedFields['email'] = 'All';
    $highlightedFields['external_identifier'] = 'All';
    $highlightedFields['first_name'] = 'Individual';
    $highlightedFields['last_name'] = 'Individual';
    $highlightedFields['household_name'] = 'Household';
    $highlightedFields['organization_name'] = 'Organization';

    foreach ($mapperKeys as $key) {
      // check if there is a _a_b or _b_a in the key
      if (strpos($key, '_a_b') || strpos($key, '_b_a')) {
        list($id, $first, $second) = explode('_', $key);
      }
      else {
        $id = $first = $second = NULL;
      }
      if (($first == 'a' && $second == 'b') || ($first == 'b' && $second == 'a')) {
        $cType = $contactRelationCache[$id]["contact_type_{$second}"];

        //CRM-5125 for contact subtype specific relationshiptypes
        $cSubType = NULL;
        if (!empty($contactRelationCache[$id]["contact_sub_type_{$second}"])) {
          $cSubType = $contactRelationCache[$id]["contact_sub_type_{$second}"];
        }

        if (!$cType) {
          $cType = 'All';
        }

        $relatedFields = array();
        $relatedFields = CRM_Contact_BAO_Contact::importableFields($cType);
        unset($relatedFields['']);
        $values = array();
        foreach ($relatedFields as $name => $field) {
          $values[$name] = $field['title'];
          if (isset($hasLocationTypes[$name])) {
            $sel3[$key][$name] = $this->_location_types;
          }
          elseif ($name == 'url') {
            $sel3[$key][$name] = $websiteTypes;
          }
          else {
            $sel3[$name] = NULL;
          }
        }

        //fix to append custom group name to field name, CRM-2676
        if (empty($this->_formattedFieldNames[$cType]) || $cType == $this->_contactType) {
          $this->_formattedFieldNames[$cType] = $this->formatCustomFieldName($values);
        }

        $this->_formattedFieldNames[$cType] = array_merge($values, $this->_formattedFieldNames[$cType]);

        //Modified the Relationship fields if the fields are
        //present in dedupe rule
        if ($this->_onDuplicate != CRM_Import_Parser::DUPLICATE_NOCHECK && !empty($this->_dedupeFields[$cType]) &&
          is_array($this->_dedupeFields[$cType])
        ) {
          static $cTypeArray = array();
          if ($cType != $this->_contactType && !in_array($cType, $cTypeArray)) {
            foreach ($this->_dedupeFields[$cType] as $val) {
              if ($valTitle = CRM_Utils_Array::value($val, $this->_formattedFieldNames[$cType])) {
                $this->_formattedFieldNames[$cType][$val] = $valTitle . ' (match to contact)';
              }
            }
            $cTypeArray[] = $cType;
          }
        }

        foreach ($highlightedFields as $k => $v) {
          if ($v == $cType || $v == 'All') {
            $highlightedRelFields[$key][] = $k;
          }
        }
        $this->assign('highlightedRelFields', $highlightedRelFields);
        $sel2[$key] = $this->_formattedFieldNames[$cType];

        if (!empty($cSubType)) {
          //custom fields for sub type
          $subTypeFields = CRM_Core_BAO_CustomField::getFieldsForImport($cSubType);

          if (!empty($subTypeFields)) {
            $subType = NULL;
            foreach ($subTypeFields as $customSubTypeField => $details) {
              $subType[$customSubTypeField] = $details['title'];
              $sel2[$key] = array_merge($sel2[$key], $this->formatCustomFieldName($subType));
            }
          }
        }

        foreach ($this->_location_types as $k => $value) {
          $sel4[$key]['phone'][$k] = &$phoneTypes;
          //build array of IM service provider for related contact
          $sel4[$key]['im'][$k] = &$imProviders;
        }
      }
      else {
        $options = NULL;
        if (!empty($hasLocationTypes[$key])) {
          $options = $this->_location_types;
        }
        elseif ($key == 'url') {
          $options = $websiteTypes;
        }
        $sel2[$key] = $options;
      }
    }

    $js = "<script type='text/javascript'>\n";
    $formName = 'document.forms.' . $this->_name;
    //used to warn for mismatch column count or mismatch mapping
    $warning = 0;
    for ($i = 0; $i < $this->_columnCount; $i++) {
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', array(1 => $i)), NULL);
      $jsSet = FALSE;
      if ($this->get('savedMapping')) {
        if (isset($mappingName[$i])) {
          if ($mappingName[$i] != ts('- do not import -')) {

            if (isset($mappingRelation[$i])) {
              // relationship mapping
              switch ($this->get('contactType')) {
                case CRM_Import_Parser::CONTACT_INDIVIDUAL:
                  $contactType = 'Individual';
                  break;

                case CRM_Import_Parser::CONTACT_HOUSEHOLD:
                  $contactType = 'Household';
                  break;

                case CRM_Import_Parser::CONTACT_ORGANIZATION:
                  $contactType = 'Organization';
              }
              //CRM-5125
              $contactSubType = NULL;
              if ($this->get('contactSubType')) {
                $contactSubType = $this->get('contactSubType');
              }

              $relations = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, $contactType,
                FALSE, 'label', TRUE, $contactSubType
              );

              foreach ($relations as $key => $var) {
                if ($key == $mappingRelation[$i]) {
                  $relation = $key;
                  break;
                }
              }

              $contactDetails = strtolower(str_replace(" ", "_", $mappingName[$i]));
              $websiteTypeId  = isset($mappingWebsiteType[$i]) ? $mappingWebsiteType[$i] : NULL;
              $locationId     = isset($mappingLocation[$i]) ? $mappingLocation[$i] : 0;
              $phoneType      = isset($mappingPhoneType[$i]) ? $mappingPhoneType[$i] : NULL;
              //get provider id from saved mappings
              $imProvider = isset($mappingImProvider[$i]) ? $mappingImProvider[$i] : NULL;

              if ($websiteTypeId) {
                $defaults["mapper[$i]"] = array($relation, $contactDetails, $websiteTypeId);
                if (!$websiteTypeId) {
                  $js .= "{$formName}['mapper[$i][2]'].style.display = 'none';\n";
                }
              }
              else {
                // default for IM/phone when mapping with relation is true
                $typeId = NULL;
                if (isset($phoneType)) {
                  $typeId = $phoneType;
                }
                elseif (isset($imProvider)) {
                  $typeId = $imProvider;
                }
                $defaults["mapper[$i]"] = array($relation, $contactDetails, $locationId, $typeId);
                if (!$locationId) {
                  $js .= "{$formName}['mapper[$i][2]'].style.display = 'none';\n";
                }
              }
              // fix for edge cases, CRM-4954
              if ($contactDetails == 'image_url') {
                $contactDetails = str_replace('url', 'URL', $contactDetails);
              }

              if (!$contactDetails) {
                $js .= "{$formName}['mapper[$i][1]'].style.display = 'none';\n";
              }

              if ((!$phoneType) && (!$imProvider)) {
                $js .= "{$formName}['mapper[$i][3]'].style.display = 'none';\n";
              }
              //$js .= "{$formName}['mapper[$i][3]'].style.display = 'none';\n";
              $jsSet = TRUE;
            }
            else {
              $mappingHeader = array_keys($this->_mapperFields, $mappingName[$i]);
              $websiteTypeId = isset($mappingWebsiteType[$i]) ? $mappingWebsiteType[$i] : NULL;
              $locationId    = isset($mappingLocation[$i]) ? $mappingLocation[$i] : 0;
              $phoneType     = isset($mappingPhoneType[$i]) ? $mappingPhoneType[$i] : NULL;
              // get IM service provider id
              $imProvider = isset($mappingImProvider[$i]) ? $mappingImProvider[$i] : NULL;

              if ($websiteTypeId) {
                if (!$websiteTypeId) {
                  $js .= "{$formName}['mapper[$i][1]'].style.display = 'none';\n";
                }
                $defaults["mapper[$i]"] = array($mappingHeader[0], $websiteTypeId);
              }
              else {
                if (!$locationId) {
                  $js .= "{$formName}['mapper[$i][1]'].style.display = 'none';\n";
                }
                //default for IM/phone without related contact
                $typeId = NULL;
                if (isset($phoneType)) {
                  $typeId = $phoneType;
                }
                elseif (isset($imProvider)) {
                  $typeId = $imProvider;
                }
                $defaults["mapper[$i]"] = array($mappingHeader[0], $locationId, $typeId);
              }

              if ((!$phoneType) && (!$imProvider)) {
                $js .= "{$formName}['mapper[$i][2]'].style.display = 'none';\n";
              }

              $js .= "{$formName}['mapper[$i][3]'].style.display = 'none';\n";

              $jsSet = TRUE;
            }
          }
          else {
            $defaults["mapper[$i]"] = array();
          }
          if (!$jsSet) {
            for ($k = 1; $k < 4; $k++) {
              $js .= "{$formName}['mapper[$i][$k]'].style.display = 'none';\n";
            }
          }
        }
        else {
          // this load section to help mapping if we ran out of saved columns when doing Load Mapping
          $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_0_');\n";

          if ($hasColumnNames) {
            $defaults["mapper[$i]"] = array($this->defaultFromColumnName($this->_columnNames[$i], $columnPatterns));
          }
          else {
            $defaults["mapper[$i]"] = array($this->defaultFromData($dataPatterns, $i));
          }
        }
        //end of load mapping
      }
      else {
        $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_0_');\n";
        if ($hasColumnNames) {
          // do array search first to see if has mapped key
          $columnKey = '';
          $columnKey = array_search($this->_columnNames[$i], $this->_mapperFields);
          if (isset($this->_fieldUsed[$columnKey])) {
            $defaults["mapper[$i]"] = $columnKey;
            $this->_fieldUsed[$key] = TRUE;
          }
          else {
            // Infer the default from the column names if we have them
            $defaults["mapper[$i]"] = array(
              $this->defaultFromColumnName($this->_columnNames[$i],
                $columnPatterns
              ),
              0,
            );
          }
        }
        else {
          // Otherwise guess the default from the form of the data
          $defaults["mapper[$i]"] = array(
            $this->defaultFromData($dataPatterns, $i),
            //                     $defaultLocationType->id
            0,
          );
        }
      }
      $sel->setOptions(array($sel1, $sel2, $sel3, $sel4));
    }

    $js .= "</script>\n";
    $this->assign('initHideBoxes', $js);

    //set warning if mismatch in more than
    if (isset($mappingName) &&
      ($this->_columnCount != count($mappingName))
    ) {
      $warning++;
    }

    if ($warning != 0 && $this->get('savedMapping')) {
      $session = CRM_Core_Session::singleton();
      $session->setStatus(ts('The data columns in this import file appear to be different from the saved mapping. Please verify that you have selected the correct saved mapping before continuing.'));
    }
    else {
      $session = CRM_Core_Session::singleton();
      $session->setStatus(NULL);
    }

    $this->setDefaults($defaults);

    $this->addButtons(array(
      array(
        'type' => 'back',
        'name' => ts('<< Previous'),
      ),
       array(
         'type' => 'next',
         'name' => ts('Continue >>'),
         'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
         'isDefault' => TRUE,
       ),
       array(
         'type' => 'cancel',
         'name' => ts('Cancel'),
       ),
      )
    );
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields) {
    $errors = array();
    if (!empty($fields['saveMapping'])) {
      $nameField = CRM_Utils_Array::value('saveMappingName', $fields);
      if (empty($nameField)) {
        $errors['saveMappingName'] = ts('Name is required to save Import Mapping');
      }
      else {
        $mappingTypeId = CRM_Core_OptionGroup::getValue('mapping_type', 'Import Contact', 'name');
        if (CRM_Core_BAO_Mapping::checkMapping($nameField, $mappingTypeId)) {
          $errors['saveMappingName'] = ts('Duplicate Import Mapping Name');
        }
      }
    }
    $template = CRM_Core_Smarty::singleton();
    if (!empty($fields['saveMapping'])) {
      $template->assign('isCheked', TRUE);
    }

    if (!empty($errors)) {
      $_flag = 1;
      $assignError = new CRM_Core_Page();
      $assignError->assign('mappingDetailsError', $_flag);
      return $errors;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Process the mapped fields and map it into the uploaded file
   * preview the file and extract some summary statistics
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $params = $this->controller->exportValues('MapField');

    //reload the mapfield if load mapping is pressed
    if (!empty($params['savedMapping'])) {
      $this->set('savedMapping', $params['savedMapping']);
      $this->controller->resetPage($this->_name);
      return;
    }

    $mapper         = array();
    $mapperKeys     = array();
    $mapperKeys     = $this->controller->exportValue($this->_name, 'mapper');
    $mapperKeysMain = array();

    $phoneTypes    = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $imProviders   = CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id');
    $websiteTypes  = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Website', 'website_type_id');
    $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');

    //these mapper params need to set key as array and val as null.
    $mapperParams = array(
      'related' => 'relatedVal',
      'locations' => 'locationsVal',
      'mapperLocType' => 'mapperLocTypeVal',
      'mapperPhoneType' => 'mapperPhoneTypeVal',
      'mapperImProvider' => 'mapperImProviderVal',
      'mapperWebsiteType' => 'mapperWebsiteTypeVal',
      'relatedContactType' => 'relatedContactTypeVal',
      'relatedContactDetails' => 'relatedContactDetailsVal',
      'relatedContactLocType' => 'relatedContactLocTypeVal',
      'relatedContactPhoneType' => 'relatedContactPhoneTypeVal',
      'relatedContactImProvider' => 'relatedContactImProviderVal',
      'relatedContactWebsiteType' => 'relatedContactWebsiteTypeVal',
    );

    //set respective mapper params to array.
    foreach (array_keys($mapperParams) as $mapperParam)$$mapperParam = array();

    for ($i = 0; $i < $this->_columnCount; $i++) {
      //set respective mapper value to null
      foreach (array_values($mapperParams) as $mapperParam)$$mapperParam = NULL;

      $fldName            = CRM_Utils_Array::value(0, $mapperKeys[$i]);
      $selOne             = CRM_Utils_Array::value(1, $mapperKeys[$i]);
      $selTwo             = CRM_Utils_Array::value(2, $mapperKeys[$i]);
      $selThree           = CRM_Utils_Array::value(3, $mapperKeys[$i]);
      $mapper[$i]         = $this->_mapperFields[$mapperKeys[$i][0]];
      $mapperKeysMain[$i] = $fldName;

      //need to differentiate non location elements.
      if ($selOne && is_numeric($selOne)) {
        if ($fldName == 'url') {
          $mapperWebsiteTypeVal = $websiteTypes[$selOne];
        }
        else {
          $locationsVal = $locationTypes[$selOne];
          $mapperLocTypeVal = $selOne;
          if ($selTwo && is_numeric($selTwo)) {
            if ($fldName == 'phone') {
              $mapperPhoneTypeVal = $phoneTypes[$selTwo];
            }
            elseif ($fldName == 'im') {
              $mapperImProviderVal = $imProviders[$selTwo];
            }
          }
        }
      }

      //relationship contact mapper info.
      list($id, $first, $second) = CRM_Utils_System::explode('_', $fldName, 3);
      if (($first == 'a' && $second == 'b') ||
        ($first == 'b' && $second == 'a')
      ) {
        $relatedVal = $this->_mapperFields[$fldName];
        if ($selOne) {
          if ($selOne == 'url') {
            $relatedContactWebsiteTypeVal = $websiteTypes[$selTwo];
          }
          else {
            $relatedContactLocTypeVal = CRM_Utils_Array::value($selTwo, $locationTypes);
            if ($selThree) {
              if ($selOne == 'phone') {
                $relatedContactPhoneTypeVal = $phoneTypes[$selThree];
              }
              elseif ($selOne == 'im') {
                $relatedContactImProviderVal = $imProviders[$selThree];
              }
            }
          }

          //get the related contact type.
          $relationType = new CRM_Contact_DAO_RelationshipType();
          $relationType->id = $id;
          $relationType->find(TRUE);
          $relatedContactTypeVal = $relationType->{"contact_type_$second"};
          $relatedContactDetailsVal = $this->_formattedFieldNames[$relatedContactTypeVal][$selOne];
        }
      }

      //set the respective mapper param array values.
      foreach ($mapperParams as $mapperParamKey => $mapperParamVal) {
        ${$mapperParamKey}[$i] = $$mapperParamVal;
      }
    }

    $this->set('columnNames', $this->_columnNames);

    //set main contact properties.
    $properties = array(
      'ims' => 'mapperImProvider',
      'mapper' => 'mapper',
      'phones' => 'mapperPhoneType',
      'websites' => 'mapperWebsiteType',
      'locations' => 'locations',
    );
    foreach ($properties as $propertyName => $propertyVal) {
      $this->set($propertyName, $$propertyVal);
    }

    //set related contact propeties.
    $relProperties = array(
      'related', 'relatedContactType', 'relatedContactDetails',
      'relatedContactLocType', 'relatedContactPhoneType', 'relatedContactImProvider',
      'relatedContactWebsiteType',
    );
    foreach ($relProperties as $relProperty) {
      $this->set($relProperty, $$relProperty);
    }

    // store mapping Id to display it in the preview page
    $this->set('loadMappingId', CRM_Utils_Array::value('mappingId', $params));

    //Updating Mapping Records
    if (!empty($params['updateMapping'])) {

      $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');

      $mappingFields = new CRM_Core_DAO_MappingField();
      $mappingFields->mapping_id = $params['mappingId'];
      $mappingFields->find();

      $mappingFieldsId = array();
      while ($mappingFields->fetch()) {
        if ($mappingFields->id) {
          $mappingFieldsId[$mappingFields->column_number] = $mappingFields->id;
        }
      }

      for ($i = 0; $i < $this->_columnCount; $i++) {
        $updateMappingFields = new CRM_Core_DAO_MappingField();
        $updateMappingFields->id = CRM_Utils_Array::value($i,$mappingFieldsId);
        $updateMappingFields->mapping_id = $params['mappingId'];
        $updateMappingFields->column_number = $i;

        $mapperKeyParts = explode('_', $mapperKeys[$i][0], 3);
        $id             = isset($mapperKeyParts[0]) ? $mapperKeyParts[0] : NULL;
        $first          = isset($mapperKeyParts[1]) ? $mapperKeyParts[1] : NULL;
        $second         = isset($mapperKeyParts[2]) ? $mapperKeyParts[2] : NULL;
        if (($first == 'a' && $second == 'b') || ($first == 'b' && $second == 'a')) {
          $updateMappingFields->relationship_type_id = $id;
          $updateMappingFields->relationship_direction = "{$first}_{$second}";
          $updateMappingFields->name = ucwords(str_replace("_", " ", $mapperKeys[$i][1]));
          // get phoneType id and provider id separately
          // before updating mappingFields of phone and IM for related contact, CRM-3140
          if (CRM_Utils_Array::value('1', $mapperKeys[$i]) == 'url') {
            $updateMappingFields->website_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
          }
          else {
            if (CRM_Utils_Array::value('1', $mapperKeys[$i]) == 'phone') {
              $updateMappingFields->phone_type_id = isset($mapperKeys[$i][3]) ? $mapperKeys[$i][3] : NULL;
            }
            elseif (CRM_Utils_Array::value('1', $mapperKeys[$i]) == 'im') {
              $updateMappingFields->im_provider_id = isset($mapperKeys[$i][3]) ? $mapperKeys[$i][3] : NULL;
            }
            $updateMappingFields->location_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
          }
        }
        else {
          $updateMappingFields->name = $mapper[$i];
          $updateMappingFields->relationship_type_id = 'NULL';
          $updateMappingFields->relationship_type_direction = 'NULL';
          // to store phoneType id and provider id seperately
          // before updating mappingFields for phone and IM, CRM-3140
          if (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'url') {
            $updateMappingFields->website_type_id = isset($mapperKeys[$i][1]) ? $mapperKeys[$i][1] : NULL;
          }
          else {
            if (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'phone') {
              $updateMappingFields->phone_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
            }
            elseif (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'im') {
              $updateMappingFields->im_provider_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
            }
            $location = array_keys($locationTypes, $locations[$i]);
            $updateMappingFields->location_type_id = (isset($location) && isset($location[0])) ? $location[0] : NULL;
          }
        }
        $updateMappingFields->save();
      }
    }

    //Saving Mapping Details and Records
    if (!empty($params['saveMapping'])) {
      $mappingParams = array(
        'name' => $params['saveMappingName'],
        'description' => $params['saveMappingDesc'],
        'mapping_type_id' => CRM_Core_OptionGroup::getValue('mapping_type',
          'Import Contact',
          'name'
        ),
      );

      $saveMapping = CRM_Core_BAO_Mapping::add($mappingParams);

      $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
      $contactType = $this->get('contactType');
      switch ($contactType) {
        case CRM_Import_Parser::CONTACT_INDIVIDUAL:
          $cType = 'Individual';
          break;

        case CRM_Import_Parser::CONTACT_HOUSEHOLD:
          $cType = 'Household';
          break;

        case CRM_Import_Parser::CONTACT_ORGANIZATION:
          $cType = 'Organization';
      }

      for ($i = 0; $i < $this->_columnCount; $i++) {
        $saveMappingFields = new CRM_Core_DAO_MappingField();
        $saveMappingFields->mapping_id = $saveMapping->id;
        $saveMappingFields->contact_type = $cType;
        $saveMappingFields->column_number = $i;

        $mapperKeyParts = explode('_', $mapperKeys[$i][0], 3);
        $id             = isset($mapperKeyParts[0]) ? $mapperKeyParts[0] : NULL;
        $first          = isset($mapperKeyParts[1]) ? $mapperKeyParts[1] : NULL;
        $second         = isset($mapperKeyParts[2]) ? $mapperKeyParts[2] : NULL;
        if (($first == 'a' && $second == 'b') || ($first == 'b' && $second == 'a')) {
          $saveMappingFields->name = ucwords(str_replace("_", " ", $mapperKeys[$i][1]));
          $saveMappingFields->relationship_type_id = $id;
          $saveMappingFields->relationship_direction = "{$first}_{$second}";
          // to get phoneType id and provider id seperately
          // before saving mappingFields of phone and IM for related contact, CRM-3140
          if (CRM_Utils_Array::value('1', $mapperKeys[$i]) == 'url') {
            $saveMappingFields->website_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
          }
          else {
            if (CRM_Utils_Array::value('1', $mapperKeys[$i]) == 'phone') {
              $saveMappingFields->phone_type_id = isset($mapperKeys[$i][3]) ? $mapperKeys[$i][3] : NULL;
            }
            elseif (CRM_Utils_Array::value('1', $mapperKeys[$i]) == 'im') {
              $saveMappingFields->im_provider_id = isset($mapperKeys[$i][3]) ? $mapperKeys[$i][3] : NULL;
            }
            $saveMappingFields->location_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
          }
        }
        else {
          $saveMappingFields->name = $mapper[$i];
          $location_id = array_keys($locationTypes, $locations[$i]);
          // to get phoneType id and provider id seperately
          // before saving mappingFields of phone and IM, CRM-3140
          if (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'url') {
            $saveMappingFields->website_type_id = isset($mapperKeys[$i][1]) ? $mapperKeys[$i][1] : NULL;
          }
          else {
            if (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'phone') {
              $saveMappingFields->phone_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
            }
            elseif (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'im') {
              $saveMappingFields->im_provider_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
            }
            $saveMappingFields->location_type_id = isset($location_id[0]) ? $location_id[0] : NULL;
          }
          $saveMappingFields->relationship_type_id = NULL;
        }
        $saveMappingFields->save();
      }
      $this->set('savedMapping', $saveMappingFields->mapping_id);
    }

    $parser = new CRM_Contact_Import_Parser_Contact($mapperKeysMain, $mapperLocType, $mapperPhoneType,
      $mapperImProvider, $related, $relatedContactType,
      $relatedContactDetails, $relatedContactLocType,
      $relatedContactPhoneType, $relatedContactImProvider,
      $mapperWebsiteType, $relatedContactWebsiteType
    );

    $primaryKeyName = $this->get('primaryKeyName');
    $statusFieldName = $this->get('statusFieldName');
    $parser->run($this->_importTableName,
      $mapper,
      CRM_Import_Parser::MODE_PREVIEW,
      $this->get('contactType'),
      $primaryKeyName,
      $statusFieldName,
      $this->_onDuplicate,
      NULL, NULL, FALSE,
      CRM_Contact_Import_Parser::DEFAULT_TIMEOUT,
      $this->get('contactSubType'),
      $this->get('dedupe')
    );

    // add all the necessary variables to the form
    $parser->set($this);
  }

  /**
   * format custom field name.
   * combine group and field name to avoid conflict.
   *
   * @param $fields
   *
   * @return void
   * @access public
   */
  function formatCustomFieldName(&$fields) {
    //CRM-2676, replacing the conflict for same custom field name from different custom group.
    $fieldIds = $formattedFieldNames = array();
    foreach ($fields as $key => $value) {
      if ($customFieldId = CRM_Core_BAO_CustomField::getKeyID($key)) {
        $fieldIds[] = $customFieldId;
      }
    }

    if (!empty($fieldIds) && is_array($fieldIds)) {
      $groupTitles = CRM_Core_BAO_CustomGroup::getGroupTitles($fieldIds);

      if (!empty($groupTitles)) {
        foreach ($groupTitles as $fId => $values) {
          $key = "custom_{$fId}";
          $groupTitle = $values['groupTitle'];
          $formattedFieldNames[$key] = $fields[$key] . ' :: ' . $groupTitle;
        }
      }
    }

    return $formattedFieldNames;
  }
}

