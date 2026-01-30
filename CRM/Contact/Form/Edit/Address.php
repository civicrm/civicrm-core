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
 * This class is used to build address block.
 */
class CRM_Contact_Form_Edit_Address {

  /**
   * Build form for address input fields.
   *
   * @param CRM_Core_Form $form
   * @param int $addressBlockCount
   *   The index of the address array (if multiple addresses on a page).
   * @param bool $sharing
   *   False, if we want to skip the address sharing features.
   * @param bool $inlineEdit
   *   True when edit used in inline edit.
   *
   * @throws \CRM_Core_Exception
   */
  public static function buildQuickForm(&$form, $addressBlockCount = NULL, $sharing = TRUE, $inlineEdit = FALSE) {
    // passing this via the session is AWFUL. we need to fix this
    if (!$addressBlockCount) {
      $blockId = ($form->get('Address_Block_Count')) ? $form->get('Address_Block_Count') : 1;
    }
    else {
      $blockId = $addressBlockCount;
    }

    $form->applyFilter('__ALL__', 'trim');

    $js = [];
    if (!$inlineEdit) {
      $js = ['onChange' => 'checkLocation( this.id );', 'placeholder' => NULL];
    }

    //make location type required for inline edit
    $form->addField("address[$blockId][location_type_id]", ['entity' => 'address', 'class' => 'eight', 'option_url' => NULL] + $js, $inlineEdit);
    if (!$inlineEdit) {
      $js = ['id' => 'Address_' . $blockId . '_IsPrimary', 'onClick' => 'singleSelect( this.id );'];
    }

    $form->addField(
      "address[$blockId][is_primary]", [
        'entity' => 'address',
        'type' => 'CheckBox',
        'label' => ts('Primary location for this contact'),
        'text' => ts('Primary location for this contact'),
      ] + $js);

    if (!$inlineEdit) {
      $js = ['id' => 'Address_' . $blockId . '_IsBilling', 'onClick' => 'singleSelect( this.id );'];
    }

    $form->addField(
      "address[$blockId][is_billing]", [
        'entity' => 'address',
        'label' => ts('Billing location for this contact'),
        'text' => ts('Billing location for this contact'),
      ] + $js);

    // hidden element to store master address id
    $form->addField("address[$blockId][master_id]", ['entity' => 'address', 'type' => 'hidden']);
    $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options', TRUE, NULL, TRUE
    );

    $elements = [
      'address_name',
      'street_address',
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
      'city',
      'postal_code',
      'postal_code_suffix',
      'country_id',
      'state_province_id',
      'county_id',
      'geo_code_1',
      'geo_code_2',
      'street_number',
      'street_name',
      'street_unit',
    ];

    foreach ($elements as $name) {
      //Remove id from name, to allow comparison against enabled addressOptions.
      $nameWithoutID = str_contains($name, '_id') ? substr($name, 0, -3) : $name;
      // Skip fields which are not enabled in the address options.
      if (empty($addressOptions[$nameWithoutID])) {
        $continue = TRUE;
        //Don't skip street parsed fields when parsing is enabled.
        if (in_array($nameWithoutID, [
          'street_number',
          'street_name',
          'street_unit',
        ]) && !empty($addressOptions['street_address_parsing'])) {
          $continue = FALSE;
        }
        if ($continue) {
          continue;
        }
      }
      if ($name === 'address_name') {
        $name = 'name';
      }

      $params = ['entity' => 'Address'];

      if ($name === 'postal_code_suffix') {
        $params['label'] = ts('Suffix');
      }

      $form->addField("address[$blockId][$name]", $params);
    }

    $entityId = NULL;
    if (!empty($form->_values['address'][$blockId])) {
      $entityId = $form->_values['address'][$blockId]['id'];
    }

    // CRM-11665 geocode override option
    $geoCode = FALSE;
    if (CRM_Utils_GeocodeProvider::getUsableClassName()) {
      $geoCode = TRUE;
      $form->addElement('checkbox',
        "address[$blockId][manual_geo_code]",
        ts('Override automatic geocoding')
      );
    }
    $form->assign('geoCode', $geoCode);

    self::addCustomDataToForm($form, $entityId, $blockId);

    if ($sharing) {
      // shared address
      $form->addElement('checkbox', "address[$blockId][use_shared_address]", NULL, ts('Use another contact\'s address'));

      // Override the default profile links to add address form
      $profileLinks = CRM_Contact_BAO_Contact::getEntityRefCreateLinks('shared_address');
      $form->addEntityRef("address[$blockId][master_contact_id]", ts('Share With'), ['create' => $profileLinks, 'api' => ['extra' => ['contact_type']]]);

      // do we want to update employer for shared address
      $employer_label = '<span class="addrel-employer">' . ts('Set this organization as current employer') . '</span>';
      $household_label = '<span class="addrel-household">' . ts('Create a household member relationship with this contact') . '</span>';
      $form->addElement('checkbox', "address[$blockId][add_relationship]", NULL, $employer_label . $household_label);
    }
  }

  /**
   * Check for correct state / country mapping.
   *
   * @param array $fields
   * @param array $files
   * @param CRM_Core_Form $self
   *
   * @return array|bool
   *   if no errors
   */
  public static function formRule($fields, $files = [], $self = NULL) {
    $errors = [];

    if (!empty($fields['address']) && is_array($fields['address'])) {
      foreach ($fields['address'] as $instance => $addressValues) {
        if (!empty($addressValues['use_shared_address']) && empty($addressValues['master_id'])) {
          $errors["address[$instance][use_shared_address]"] = ts('Please select valid shared contact or a contact with valid address.');
        }
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Set default values for address block.
   *
   * @param array $defaults
   *   Defaults associated array.
   * @param \CRM_Contact_Form_Contact|\CRM_Contact_Form_Edit_Address $form
   *   Form object.
   */
  public static function setDefaultValues(&$defaults, &$form) {
    $addressValues = [];
    // Actual values will be assigned to these below if there are some.
    $form->assign('masterAddress');
    $form->assign('sharedAddresses', []);
    if (isset($defaults['address']) && is_array($defaults['address']) &&
      !CRM_Utils_System::isNull($defaults['address'])
    ) {

      // start of contact shared adddress defaults
      $sharedAddresses = [];
      $masterAddress = [];

      // get contact name of shared contact names
      $shareAddressContactNames = CRM_Contact_BAO_Contact_Utils::getAddressShareContactNames($defaults['address']);

      foreach ($defaults['address'] as $key => $addressValue) {
        if (!empty($addressValue['master_id']) && !$shareAddressContactNames[$addressValue['master_id']]['is_deleted']) {
          $master_cid = $shareAddressContactNames[$addressValue['master_id']]['contact_id'];
          $sharedAddresses[$key]['shared_address_display'] = [
            'address' => $addressValue['display'],
            'name' => $shareAddressContactNames[$addressValue['master_id']]['name'],
            'options' => CRM_Core_BAO_Address::getValues([
              'entity_id' => $master_cid,
              'contact_id' => $master_cid,
            ]),
            'master_id' => $addressValue['master_id'],
          ];
          $defaults['address'][$key]['master_contact_id'] = $master_cid;
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
        $parseFields = ['street_address', 'street_number', 'street_name', 'street_unit'];
        foreach ($defaults['address'] as $cnt => & $address) {
          $streetAddress = NULL;
          foreach ([
            'street_number',
            'street_number_suffix',
            'street_name',
            'street_unit',
          ] as $fld) {
            if (in_array($fld, ['street_name', 'street_unit'])) {
              $streetAddress .= ' ';
            }
            // CRM-17619 - if the street number suffix begins with a number, add a space
            $numsuffix = $address[$fld] ?? NULL;
            if ($fld === 'street_number_suffix' && !empty($numsuffix)) {
              if (ctype_digit(substr($numsuffix, 0, 1))) {
                $streetAddress .= ' ';
              }
            }
            $streetAddress .= $address[$fld] ?? '';
          }
          $streetAddress = trim($streetAddress);
          if (!empty($streetAddress)) {
            $address['street_address'] = $streetAddress;
          }
          if (isset($address['street_number'])) {
            // CRM-17619 - if the street number suffix begins with a number, add a space
            $thesuffix = $address['street_number_suffix'] ?? NULL;
            if ($thesuffix) {
              if (ctype_digit(substr($thesuffix, 0, 1))) {
                $address['street_number'] .= " ";
              }
            }
            $address['street_number'] .= $thesuffix;
          }
          // build array for set default.
          foreach ($parseFields as $field) {
            $addressValues["{$field}_{$cnt}"] = $address[$field] ?? NULL;
          }
          // don't load fields, use js to populate.
          foreach (['street_number', 'street_name', 'street_unit'] as $f) {
            if (isset($address[$f])) {
              unset($address[$f]);
            }
          }
        }
        $form->assign('allAddressFieldValues', json_encode($addressValues));

        //hack to handle show/hide address fields.
        $parsedAddress = [];
        if ($form->_contactId && !empty($_POST['address']) && is_array($_POST['address'])
        ) {
          foreach ($_POST['address'] as $cnt => $values) {
            $showField = 'streetAddress';
            foreach (['street_number', 'street_name', 'street_unit'] as $fld) {
              if (!empty($values[$fld])) {
                $showField = 'addressElements';
                break;
              }
            }
            $parsedAddress[$cnt] = $showField;
          }
        }
        $form->assign('showHideAddressFields', $parsedAddress);
        $form->assign('loadShowHideAddressFields', !empty($parsedAddress));
      }
      // end of parse address functionality
    }
  }

  /**
   * Add custom data to the form.
   *
   * @param CRM_Core_Form $form
   * @param int $entityId
   * @param int $blockId
   *
   * @throws \CRM_Core_Exception
   */
  protected static function addCustomDataToForm(&$form, $entityId, $blockId) {
    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Address', NULL, $entityId);
    if (isset($groupTree) && is_array($groupTree)) {
      // use simplified formatted groupTree
      $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, 1, $form);
      $enforceRequiredFields = self::enforceRequired($form);
      // make sure custom fields are added /w element-name in the format - 'address[$blockId][custom-X]'
      foreach ($groupTree as $id => $group) {
        foreach ($group['fields'] as $fieldID => $field) {
          $groupTree[$id]['fields'][$fieldID]['element_custom_name'] = $field['element_name'];
          $groupTree[$id]['fields'][$fieldID]['element_name'] = "address[$blockId][{$field['element_name']}]";
          if (!$enforceRequiredFields) {
            $groupTree[$id]['fields'][$fieldID]['is_required'] = 0;
          }
        }
      }
      $defaults = [];
      CRM_Core_BAO_CustomGroup::setDefaults($groupTree, $defaults);

      // since we change element name for address custom data, we need to format the setdefault values
      $addressDefaults = [];
      foreach ($defaults as $key => $val) {
        if (!isset($val)) {
          continue;
        }

        // in order to set correct defaults for checkbox custom data, we need to converted flat key to array
        // this works for all types custom data
        $keyValues = explode('[', str_replace(']', '', $key));
        $addressDefaults[$keyValues[0]][$keyValues[1]][$keyValues[2]] = $val;
      }

      $form->setDefaults($addressDefaults);

      // we setting the prefix to 'dnc_' below, so that we don't overwrite smarty's grouptree var.
      // And we can't set it to 'address_' because we want to set it in a slightly different format.
      CRM_Core_BAO_CustomGroup::buildQuickForm($form, $groupTree, FALSE, 'dnc_');

      $tplGroupTree = CRM_Core_Smarty::singleton()
        ->getTemplateVars('address_groupTree');
      $tplGroupTree = empty($tplGroupTree) ? [] : $tplGroupTree;

      $form->assign('address_groupTree', $tplGroupTree + [$blockId => $groupTree]);
      // unset the temp smarty var that got created
      $form->assign('dnc_groupTree', NULL);
    }
    // address custom data processing ends ..
  }

  /**
   * Should required fields be enforced.
   *
   * This would be FALSE if there were no other address fields present.
   *
   * @param \CRM_Core_Form $form
   *
   * @return bool
   */
  private static function enforceRequired(CRM_Core_Form $form): bool {
    if ($form->isSubmitted()) {
      $addresses = (array) $form->getSubmittedValue('address');
      foreach ($addresses as $idx => $address) {
        $address['id'] = $form->_values['address'][$idx]['id'] ?? "";
        if (!empty($address['master_id']) || CRM_Core_BAO_Address::dataExists($address)) {
          return TRUE;
        }
      }
      return FALSE;
    }
    return TRUE;
  }

}
