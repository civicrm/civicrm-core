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

/**
 * This class is used to build address block
 */
class CRM_Contact_Form_Edit_Address {

  /**
   * build form for address input fields
   *
   * @param object  $form - CRM_Core_Form (or subclass)
   * @param int     $addressBlockCount - the index of the address array (if multiple addresses on a page)
   * @param boolean $sharing - false, if we want to skip the address sharing features
   * @param boolean $inlineEdit true when edit used in inline edit
   *
   * @return none
   *
   * @access public
   * @static
   */
  static function buildQuickForm(&$form, $addressBlockCount = NULL, $sharing = TRUE, $inlineEdit = FALSE) {
    // passing this via the session is AWFUL. we need to fix this
    if (!$addressBlockCount) {
      $blockId = ($form->get('Address_Block_Count')) ? $form->get('Address_Block_Count') : 1;
    }
    else {
      $blockId = $addressBlockCount;
    }

    $config = CRM_Core_Config::singleton();
    $countryDefault = $config->defaultContactCountry;

    $form->applyFilter('__ALL__', 'trim');

    $js = array();
    if ( !$inlineEdit ) {
    $js = array('onChange' => 'checkLocation( this.id );');
    }

    $form->addElement('select',
      "address[$blockId][location_type_id]",
      ts('Location Type'),
      array(
        '' => ts('- select -')) + CRM_Core_PseudoConstant::locationType(),
        $js
    );

    if ( !$inlineEdit ) {
    $js = array('id' => 'Address_' . $blockId . '_IsPrimary', 'onClick' => 'singleSelect( this.id );');
    }
    else {
      //make location type required for inline edit
      $form->addRule( "address[$blockId][location_type_id]", ts('%1 is a required field.', array(1 => ts('Location Type'))), 'required');
    }

    $form->addElement(
      'checkbox',
      "address[$blockId][is_primary]",
      ts('Primary location for this contact'),
      ts('Primary location for this contact'),
      $js
    );

    if ( !$inlineEdit ) {
    $js = array('id' => 'Address_' . $blockId . '_IsBilling', 'onClick' => 'singleSelect( this.id );');
    }

    $form->addElement(
      'checkbox',
      "address[$blockId][is_billing]",
      ts('Billing location for this contact'),
      ts('Billing location for this contact'),
      $js
    );

    // hidden element to store master address id
    $form->addElement('hidden', "address[$blockId][master_id]");

    $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options', TRUE, NULL, TRUE
    );
    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Address');

    $elements = array(
      'address_name' => array(ts('Address Name'), $attributes['address_name'], NULL),
      'street_address' => array(ts('Street Address'), $attributes['street_address'], NULL),
      'supplemental_address_1' => array(ts('Addt\'l Address 1'), $attributes['supplemental_address_1'], NULL),
      'supplemental_address_2' => array(ts('Addt\'l Address 2'), $attributes['supplemental_address_2'], NULL),
      'city' => array(ts('City'), $attributes['city'], NULL),
      'postal_code' => array(ts('Zip / Postal Code'), array_merge($attributes['postal_code'], array('class' => 'crm_postal_code')), NULL),
      'postal_code_suffix' => array(ts('Postal Code Suffix'), array('size' => 4, 'maxlength' => 12, 'class' => 'crm_postal_code_suffix'), NULL),
      'county_id' => array(ts('County'), $attributes['county_id'], NULL),
      'state_province_id' => array(ts('State / Province'), $attributes['state_province_id'], NULL),
      'country_id' => array(ts('Country'), $attributes['country_id'], NULL),
      'geo_code_1' => array(ts('Latitude'), array('size' => 9, 'maxlength' => 11), NULL),
      'geo_code_2' => array(ts('Longitude'), array('size' => 9, 'maxlength' => 11), NULL),
      'street_number' => array(ts('Street Number'), $attributes['street_number'], NULL),
      'street_name' => array(ts('Street Name'), $attributes['street_name'], NULL),
      'street_unit' => array(ts('Apt/Unit/Suite'), $attributes['street_unit'], NULL),
    );

    $stateCountryMap = array();
    foreach ($elements as $name => $v) {
      list($title, $attributes, $select) = $v;

      $nameWithoutID = strpos($name, '_id') !== FALSE ? substr($name, 0, -3) : $name;
      if (!CRM_Utils_Array::value($nameWithoutID, $addressOptions)) {
        $continue = TRUE;
        if (in_array($nameWithoutID, array(
          'street_number', 'street_name', 'street_unit')) &&
          CRM_Utils_Array::value('street_address_parsing', $addressOptions)
        ) {
          $continue = FALSE;
        }
        if ($continue) {
          continue;
        }
      }

      if (!$attributes) {
        $attributes = $attributes[$name];
      }

      //build normal select if country is not present in address block
      if ($name == 'state_province_id' && !$addressOptions['country']) {
        $select = 'stateProvince';
      }

      if (!$select) {
        if ($name == 'country_id' || $name == 'state_province_id' || $name == 'county_id') {
          if ($name == 'country_id') {
            $stateCountryMap[$blockId]['country'] = "address_{$blockId}_{$name}";
            $selectOptions = array('' => ts('- select -')) + CRM_Core_PseudoConstant::country();
          }
          elseif ($name == 'state_province_id') {
            $stateCountryMap[$blockId]['state_province'] = "address_{$blockId}_{$name}";
            if ($countryDefault) {
              $selectOptions = array('' => ts('- select -')) + CRM_Core_PseudoConstant::stateProvinceForCountry($countryDefault);
            }
            else {
              $selectOptions = array('' => ts('- select a country -'));
            }
          }
          elseif ($name == 'county_id') {
            $stateCountryMap[$blockId]['county'] = "address_{$blockId}_{$name}";
            if ($form->getSubmitValue("address[{$blockId}][state_province_id]")) {
              $selectOptions = array('' => ts('- select -')) + CRM_Core_PseudoConstant::countyForState($form->getSubmitValue("address[{$blockId}][state_province_id]"));
            }
            elseif ($form->getSubmitValue("address[{$blockId}][county_id]")) {
              $selectOptions = array('' => ts('- select a state -')) + CRM_Core_PseudoConstant::county();
            }
            else {
              $selectOptions = array('' => ts('- select a state -'));
            }
          }
          $form->addElement('select',
            "address[$blockId][$name]",
            $title,
            $selectOptions
          );
        }
        else {
          if ($name == 'address_name') {
            $name = 'name';
          }

          $form->addElement('text',
            "address[$blockId][$name]",
            $title,
            $attributes
          );
        }
      }
      else {
        $form->addElement('select',
          "address[$blockId][$name]",
          $title,
          array('' => ts('- select -')) + CRM_Core_PseudoConstant::$select()
        );
      }
    }

    CRM_Core_BAO_Address::addStateCountryMap($stateCountryMap);

    $entityId = NULL;
    if (!empty($form->_values['address']) && CRM_Utils_Array::value($blockId, $form->_values['address'])) {
      $entityId = $form->_values['address'][$blockId]['id'];
    }

    // CRM-11665 geocode override option
    $geoCode = FALSE;
    if (!empty($config->geocodeMethod)) {
      $geoCode = TRUE;
      $form->addElement('checkbox',
        "address[$blockId][manual_geo_code]",
        ts('Override automatic geocoding')
      );
    }
    $form->assign('geoCode', $geoCode);

    // Process any address custom data -
    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Address',
      $form,
      $entityId
    );

    if (isset($groupTree) && is_array($groupTree)) {
      // use simplified formatted groupTree
      $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, 1, $form);

      // make sure custom fields are added /w element-name in the format - 'address[$blockId][custom-X]'
      foreach ($groupTree as $id => $group) {
        foreach ($group['fields'] as $fldId => $field) {
          $groupTree[$id]['fields'][$fldId]['element_custom_name'] = $field['element_name'];
          $groupTree[$id]['fields'][$fldId]['element_name'] = "address[$blockId][{$field['element_name']}]";
        }
      }

      $defaults = array();
      CRM_Core_BAO_CustomGroup::setDefaults($groupTree, $defaults);

      // since we change element name for address custom data, we need to format the setdefault values
      $addressDefaults = array();
      foreach ($defaults as $key => $val) {
        if ( empty( $val ) ) {
          continue;
        }

        // inorder to set correct defaults for checkbox custom data, we need to converted flat key to array
        // this works for all types custom data
        $keyValues = explode('[', str_replace(']', '', $key));
        $addressDefaults[$keyValues[0]][$keyValues[1]][$keyValues[2]] = $val;
      }

      $form->setDefaults($addressDefaults);

      // we setting the prefix to 'dnc_' below, so that we don't overwrite smarty's grouptree var.
      // And we can't set it to 'address_' because we want to set it in a slightly different format.
      CRM_Core_BAO_CustomGroup::buildQuickForm($form, $groupTree, FALSE, 1, 'dnc_');

      $template     = CRM_Core_Smarty::singleton();
      $tplGroupTree = $template->get_template_vars('address_groupTree');
      $tplGroupTree = empty($tplGroupTree) ? array(
        ) : $tplGroupTree;

      $form->assign('address_groupTree', $tplGroupTree + array($blockId => $groupTree));
      // unset the temp smarty var that got created
      $form->assign('dnc_groupTree', NULL);
    }
    // address custom data processing ends ..

    if ($sharing) {
      // shared address
      $form->addElement('checkbox', "address[$blockId][use_shared_address]", NULL, ts('Use another contact\'s address'));

      // get the reserved for address
      $profileId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', 'shared_address', 'id', 'name');

      if (!$profileId) {
        CRM_Core_Error::fatal(ts('Your install is missing required "Shared Address" profile.'));
      }

      CRM_Contact_Form_NewContact::buildQuickForm($form, $blockId, array($profileId));
    }
  }

  /**
   * check for correct state / country mapping.
   *
   * @param array reference $fields - submitted form values.
   * @param array reference $errors - if any errors found add to this array. please.
   *
   * @return true if no errors
   *         array of errors if any present.
   *
   * @access public
   * @static
   */
  static function formRule($fields) {
    $errors = array();
    // check for state/county match if not report error to user.
    if (CRM_Utils_Array::value('address', $fields) && is_array($fields['address'])) {
      foreach ($fields['address'] as $instance => $addressValues) {
        if (CRM_Utils_System::isNull($addressValues)) {
          continue;
        }

        $countryId = CRM_Utils_Array::value('country_id', $addressValues);
        $stateProvinceId = CRM_Utils_Array::value('state_province_id', $addressValues);

        //do check for mismatch countries
        if ($stateProvinceId && $countryId) {
          $stateProvinceDAO = new CRM_Core_DAO_StateProvince();
          $stateProvinceDAO->id = $stateProvinceId;
          $stateProvinceDAO->find(TRUE);
          if ($stateProvinceDAO->country_id != $countryId) {
            // countries mismatch hence display error
            $stateProvinces = CRM_Core_PseudoConstant::stateProvince();
            $countries = CRM_Core_PseudoConstant::country();
            $errors["address[$instance][state_province_id]"] = ts('State/Province %1 is not part of %2. It belongs to %3.',
              array(
                1 => $stateProvinces[$stateProvinceId],
                2 => $countries[$countryId],
                3 => $countries[$stateProvinceDAO->country_id]
              )
            );
          }
        }

        $countyId = CRM_Utils_Array::value('county_id', $addressValues);

        //state county validation
        if ($stateProvinceId && $countyId) {
          $countyDAO = new CRM_Core_DAO_County();
          $countyDAO->id = $countyId;
          $countyDAO->find(TRUE);
          if ($countyDAO->state_province_id != $stateProvinceId) {
            $counties = CRM_Core_PseudoConstant::county();
            $errors["address[$instance][county_id]"] = ts('County %1 is not part of %2. It belongs to %3.',
              array(
                1 => $counties[$countyId],
                2 => $stateProvinces[$stateProvinceId],
                3 => $stateProvinces[$countyDAO->state_province_id]
              )
            );
          }
        }

        if (CRM_Utils_Array::value('use_shared_address', $addressValues) && !CRM_Utils_Array::value('master_id', $addressValues)) {
          $errors["address[$instance][use_shared_address]"] = ts('Please select valid shared contact or a contact with valid address.');
        }
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  static function fixStateSelect(&$form,
    $countryElementName,
    $stateElementName,
    $countyElementName,
    $countryDefaultValue,
    $stateDefaultValue = NULL
  ) {
    $countryID = $stateID = NULL;
    if (isset($form->_elementIndex[$countryElementName])) {
      //get the country id to load states -
      //first check for submitted value,
      //then check for user passed value.
      //finally check for element default val.
      $submittedVal = $form->getSubmitValue($countryElementName);
      if ($submittedVal) {
        $countryID = $submittedVal;
      }
      elseif ($countryDefaultValue) {
        $countryID = $countryDefaultValue;
      }
      else {
        $countryID = CRM_Utils_Array::value(0, $form->getElementValue($countryElementName));
      }
    }

    $stateTitle = ts('State/Province');
    if (isset($form->_fields[$stateElementName]['title'])) {
      $stateTitle = $form->_fields[$stateElementName]['title'];
    }

    if ($countryID &&
      isset($form->_elementIndex[$stateElementName])
    ) {

      $submittedValState = $form->getSubmitValue($stateElementName);
      if ($submittedValState) {
        $stateID = $submittedValState;
      }
      elseif ($stateDefaultValue) {
        $stateID = $stateDefaultValue;
      }
      else {
        $stateID = CRM_Utils_Array::value(0, $form->getElementValue($stateElementName));
      }

      $stateSelect = &$form->addElement('select',
        $stateElementName,
        $stateTitle,
        array(
          '' => ts('- select -')) +
        CRM_Core_PseudoConstant::stateProvinceForCountry($countryID)
      );


      if ($stateID &&
        isset($form->_elementIndex[$stateElementName]) &&
        isset($form->_elementIndex[$countyElementName])
      ) {
        $form->addElement('select',
          $countyElementName,
          ts('County'),
          array(
            '' => ts('- select -')) +
          CRM_Core_PseudoConstant::countyForState($stateID)
        );
      }

      // CRM-7296 freeze the select for state if address is shared with household
      // CRM-9070 freeze the select for state if it is view only
      if (isset($form->_fields) &&
          CRM_Utils_Array::value($stateElementName, $form->_fields) &&
          (CRM_Utils_Array::value('is_shared', $form->_fields[$stateElementName]) ||
          CRM_Utils_Array::value('is_view', $form->_fields[$stateElementName]))
      ) {
        $stateSelect->freeze();
      }
    }
  }

  /**
   * function to set default values for address block
   *
   * @param array  $defaults  defaults associated array
   * @param object $form     form object
   *
   * @static
   * @access public
   */
  static function setDefaultValues( &$defaults, &$form ) {
    $addressValues = array();
    if (isset($defaults['address']) && is_array($defaults['address']) &&
      !CRM_Utils_system::isNull($defaults['address'])
    ) {

      // start of contact shared adddress defaults
      $sharedAddresses = array();
      $masterAddress = array();

      // get contact name of shared contact names
      $shareAddressContactNames = CRM_Contact_BAO_Contact_Utils::getAddressShareContactNames($defaults['address']);

      foreach ($defaults['address'] as $key => $addressValue) {
        if (CRM_Utils_Array::value('master_id', $addressValue) && !$shareAddressContactNames[$addressValue['master_id']]['is_deleted']) {
          $sharedAddresses[$key]['shared_address_display'] = array(
            'address' => $addressValue['display'],
            'name' => $shareAddressContactNames[$addressValue['master_id']]['name'],
          );
        }
        else {
          $defaults['address'][$key]['use_shared_address'] = 0;
        }

        //check if any address is shared by any other contacts
        $masterAddress[$key] = CRM_Core_BAO_Address::checkContactSharedAddress($addressValue['id']);
      }

      $form->assign('sharedAddresses', $sharedAddresses);
      $form->assign('masterAddress', $masterAddress);
      // end of shared address defaults

      // start of parse address functionality
      // build street address, CRM-5450.
      if ($form->_parseStreetAddress) {
        $parseFields = array('street_address', 'street_number', 'street_name', 'street_unit');
        foreach ($defaults['address'] as $cnt => & $address) {
          $streetAddress = NULL;
          foreach (array(
            'street_number', 'street_number_suffix', 'street_name', 'street_unit') as $fld) {
            if (in_array($fld, array(
              'street_name', 'street_unit'))) {
              $streetAddress .= ' ';
            }
            $streetAddress .= CRM_Utils_Array::value($fld, $address);
          }
          $streetAddress = trim($streetAddress);
          if (!empty($streetAddress)) {
            $address['street_address'] = $streetAddress;
          }
          if (isset($address['street_number'])) {
            $address['street_number'] .= CRM_Utils_Array::value('street_number_suffix', $address);
          }

          // build array for set default.
          foreach ($parseFields as $field) {
            $addressValues["{$field}_{$cnt}"] = CRM_Utils_Array::value($field, $address);
          }

          // don't load fields, use js to populate.
          foreach (array('street_number', 'street_name', 'street_unit') as $f) {
            if (isset($address[$f])) {
              unset($address[$f]);
            }
          }
        }
        $form->assign('allAddressFieldValues', json_encode($addressValues));

        //hack to handle show/hide address fields.
        $parsedAddress = array();
        if ($form->_contactId &&
          CRM_Utils_Array::value('address', $_POST)
          && is_array($_POST['address'])
        ) {
          foreach ($_POST['address'] as $cnt => $values) {
            $showField = 'streetAddress';
            foreach (array('street_number', 'street_name', 'street_unit') as $fld) {
              if (CRM_Utils_Array::value($fld, $values)) {
                $showField = 'addressElements';
                break;
              }
            }
            $parsedAddress[$cnt] = $showField;
          }
        }
        $form->assign('showHideAddressFields', $parsedAddress);
        $form->assign('loadShowHideAddressFields', empty($parsedAddress) ? FALSE : TRUE);
      }
      // end of parse address functionality
    }
  }
}

