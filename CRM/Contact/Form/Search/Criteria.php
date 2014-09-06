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
class CRM_Contact_Form_Search_Criteria {
  /**
   * @param $form CRM_Core_Form
   */
  static function basic(&$form) {
    $form->addElement('hidden', 'hidden_basic', 1);

    if ($form->_searchOptions['contactType']) {
      // add checkboxes for contact type
      //@todo FIXME - using the CRM_Core_DAO::VALUE_SEPARATOR creates invalid html - if you can find the form
      // this is loaded onto then replace with something like '__' & test
      $separator = CRM_Core_DAO::VALUE_SEPARATOR;
      $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements(FALSE, TRUE, $separator);

      if ($contactTypes) {
        $form->add('select', 'contact_type', ts('Contact Type(s)'), $contactTypes, FALSE,
          array('id' => 'contact_type', 'multiple' => 'multiple', 'class' => 'crm-select2', 'style' => 'width: 100%;')
        );
      }
    }

    if ($form->_searchOptions['groups']) {
      // multiselect for groups
      if ($form->_group) {
        // Arrange groups into hierarchical listing (child groups follow their parents and have indentation spacing in title)
        $groupHierarchy = CRM_Contact_BAO_Group::getGroupsHierarchy($form->_group, NULL, '&nbsp;&nbsp;', TRUE);

        $form->add('select', 'group', ts('Groups'), $groupHierarchy, FALSE,
          array('id' => 'group', 'multiple' => 'multiple', 'class' => 'crm-select2')
        );
        $groupOptions = CRM_Core_BAO_OptionValue::getOptionValuesAssocArrayFromName('group_type');
        $form->add('select', 'group_type', ts('Group Types'), $groupOptions, FALSE,
          array('id' => 'group_type', 'multiple' => 'multiple', 'class' => 'crm-select2')
        );
        $form->add('hidden','group_search_selected','group');
      }
    }

    if ($form->_searchOptions['tags']) {
      // multiselect for categories
      $contactTags = CRM_Core_BAO_Tag::getTags();

      if ($contactTags) {
        $form->add('select', 'contact_tags', ts('Tags'), $contactTags, FALSE,
          array('id' => 'contact_tags', 'multiple' => 'multiple', 'class' => 'crm-select2', 'style' => 'width: 100%;')
        );
      }

      $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_contact');
      CRM_Core_Form_Tag::buildQuickForm($form, $parentNames, 'civicrm_contact', NULL, TRUE, FALSE);

      $used_for = CRM_Core_OptionGroup::values('tag_used_for');
      $tagsTypes = array();
      $showAllTagTypes = false;
      foreach ($used_for as $key => $value) {
        //check tags for every type and find if there are any defined
        $tags = CRM_Core_BAO_Tag::getTagsUsedFor($key, FALSE, TRUE, NULL);
        // check if there are tags other than contact type, if no - keep checkbox hidden on adv search
        // we will hide searching contact by attachments tags until it will be implemented in core
        if (count($tags) && $key != 'civicrm_file' && $key != 'civicrm_contact') {
          //if tags exists then add type to display in adv search form help text
          $tagsTypes[] = ts($value);
          $showAllTagTypes = true;
        }
      }
      $tagTypesText = implode(" or ", $tagsTypes);
      if ($showAllTagTypes) {
        $form->add('checkbox', 'all_tag_types', ts('Include tags used for %1', array(1 => $tagTypesText)));
        $form->add('hidden','tag_types_text', $tagTypesText);
      }
    }

    // add text box for last name, first name, street name, city
    $form->addElement('text', 'sort_name', ts('Find...'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    // add text box for last name, first name, street name, city
    $form->add('text', 'email', ts('Contact Email'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    //added contact source
    $form->add('text', 'contact_source', ts('Contact Source'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'contact_source'));

    //added job title
    $form->addElement('text', 'job_title', ts('Job Title'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'job_title'));


    //added internal ID
    $form->addElement('text', 'contact_id', ts('Contact ID'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'id'));
    $form->addRule('contact_id', ts('Please enter valid Contact ID'), 'positiveInteger');

    //added external ID
    $form->addElement('text', 'external_identifier', ts('External ID'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'external_identifier'));

    if (CRM_Core_Permission::check('access deleted contacts') and CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'contact_undelete', NULL)) {
      $form->add('checkbox', 'deleted_contacts', ts('Search in Trash') . '<br />' . ts('(deleted contacts)'));
    }

    // add checkbox for cms users only
    $form->addYesNo('uf_user', ts('CMS User?'), TRUE);

    // tag all search
    $form->add('text', 'tag_search', ts('All Tags'));

    // add search profiles

    // FIXME: This is probably a part of profiles - need to be
    // FIXME: eradicated from here when profiles are reworked.
    $types = array('Participant', 'Contribution', 'Membership');

    // get component profiles
    $componentProfiles = array();
    $componentProfiles = CRM_Core_BAO_UFGroup::getProfiles($types);

    $ufGroups = CRM_Core_BAO_UFGroup::getModuleUFGroup('Search Profile', 1);
    $accessibleUfGroups = CRM_Core_Permission::ufGroup(CRM_Core_Permission::VIEW);

    $searchProfiles = array();
    foreach ($ufGroups as $key => $var) {
      if (!array_key_exists($key, $componentProfiles) && in_array($key, $accessibleUfGroups)) {
        $searchProfiles[$key] = $var['title'];
      }
    }

    $form->addElement('select',
      'uf_group_id',
      ts('Search Views'),
      array(
        '0' => ts('- default view -')) + $searchProfiles
    );

    $componentModes = CRM_Contact_Form_Search::getModeSelect();

    // unset contributions or participants if user does not have
    // permission on them
    if (!CRM_Core_Permission::access('CiviContribute')) {
      unset($componentModes['2']);
    }

    if (!CRM_Core_Permission::access('CiviEvent')) {
      unset($componentModes['3']);
    }

    if (!CRM_Core_Permission::access('CiviMember')) {
      unset($componentModes['5']);
    }

    if (!CRM_Core_Permission::check('view all activities')) {
      unset($componentModes['4']);
    }

    if (count($componentModes) > 1) {
      $form->addElement('select',
        'component_mode',
        ts('Display Results As'),
        $componentModes
      );
    }

    $form->addElement('select',
      'operator',
      ts('Search Operator'),
      array('AND' => ts('AND'),
        'OR' => ts('OR'),
      )
    );

    // add the option to display relationships
    $rTypes = CRM_Core_PseudoConstant::relationshipType();
    $rSelect = array('' => ts('- Select Relationship Type-'));
    foreach ($rTypes as $rid => $rValue) {
      if ($rValue['label_a_b'] == $rValue['label_b_a']) {
        $rSelect[$rid] = $rValue['label_a_b'];
      }
      else {
        $rSelect["{$rid}_a_b"] = $rValue['label_a_b'];
        $rSelect["{$rid}_b_a"] = $rValue['label_b_a'];
      }
    }

    $form->addElement('select',
      'display_relationship_type',
      ts('Display Results as Relationship'),
      $rSelect
    );

    // checkboxes for DO NOT phone, email, mail
    // we take labels from SelectValues
    $t = CRM_Core_SelectValues::privacy();
    $form->add('select',
      'privacy_options',
      ts('Privacy'),
      $t,
      FALSE,
      array(
        'id' => 'privacy_options',
        'multiple' => 'multiple',
        'class' => 'crm-select2',
      )
    );

    $form->addElement('select',
      'privacy_operator',
      ts('Operator'),
      array('OR' => ts('OR'),
        'AND' => ts('AND'),
      )
    );

    $options = array(
      1 => ts('Exclude'),
      2 => ts('Include by Privacy Option(s)'),
    );
    $form->addRadio('privacy_toggle', ts('Privacy Options'), $options, array('allowClear' => FALSE));

    // preferred communication method
    $comm = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'preferred_communication_method');

    $commPreff = array();
    foreach ($comm as $k => $v) {
      $commPreff[] = $form->createElement('advcheckbox', $k, NULL, $v);
    }

    $onHold[] = $form->createElement('advcheckbox', 'on_hold', NULL, ts(''));
    $form->addGroup($onHold, 'email_on_hold', ts('Email On Hold'));

    $form->addGroup($commPreff, 'preferred_communication_method', ts('Preferred Communication Method'));

    //CRM-6138 Preferred Language
    $form->addSelect('preferred_language', array('class' => 'twenty', 'option_url' => NULL));

    // Phone search
    $form->addElement('text', 'phone_numeric', ts('Phone Number'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone', 'phone'));
    $locationType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $phoneType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $form->add('select', 'phone_location_type_id', ts('Phone Location'), array('' => ts('- any -')) + $locationType);
    $form->add('select', 'phone_phone_type_id', ts('Phone Type'), array('' => ts('- any -')) + $phoneType);
  }


  /**
   * @param CRM_Core_Form $form
   */
  static function location(&$form) {
    $config = CRM_Core_Config::singleton();
    // Build location criteria based on _submitValues if
    // available; otherwise, use $form->_formValues.
    $formValues = $form->_submitValues;

    if (empty($formValues) && !empty($form->_formValues)) {
      $formValues = $form->_formValues;
    }

    $form->addElement('hidden', 'hidden_location', 1);

    $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options', TRUE, NULL, TRUE
    );

    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Address');

    $elements = array(
      'street_address' => array(ts('Street Address'), $attributes['street_address'], NULL, NULL),
      'city' => array(ts('City'), $attributes['city'], NULL, NULL),
      'postal_code' => array(ts('Zip / Postal Code'), $attributes['postal_code'], NULL, NULL),
      'country' => array(ts('Country'), $attributes['country_id'], 'country', FALSE),
      'state_province' => array(ts('State/Province'), $attributes['state_province_id'], 'stateProvince', TRUE),
      'county' => array(ts('County'), $attributes['county_id'], 'county', TRUE),
      'address_name' => array(ts('Address Name'), $attributes['address_name'], NULL, NULL),
      'street_number' => array(ts('Street Number'), $attributes['street_number'], NULL, NULL),
      'street_name' => array(ts('Street Name'), $attributes['street_name'], NULL, NULL),
      'street_unit' => array(ts('Apt/Unit/Suite'), $attributes['street_unit'], NULL, NULL),
    );

    $parseStreetAddress = CRM_Utils_Array::value('street_address_parsing', $addressOptions, 0);
    $form->assign('parseStreetAddress', $parseStreetAddress);
    foreach ($elements as $name => $v) {
      list($title, $attributes, $select, $multiSelect) = $v;

      if (in_array($name,
          array('street_number', 'street_name', 'street_unit')
        )) {
        if (!$parseStreetAddress) {
          continue;
        }
      }
      elseif (!$addressOptions[$name]) {
        continue;
      }

      if (!$attributes) {
        $attributes = $attributes[$name];
      }

      if ($select) {
        if ($select == 'stateProvince' || $select == 'county') {
          $element = $form->addChainSelect($name);
        }
        else {
          $selectElements = array('' => ts('- any -')) + CRM_Core_PseudoConstant::$select();
          $element = $form->add('select', $name, $title, $selectElements, FALSE, array('class' => 'crm-select2'));
        }
        if ($multiSelect) {
          $element->setMultiple(TRUE);
        }
      }
      else {
        $form->addElement('text', $name, $title, $attributes);
      }

      if ($addressOptions['postal_code']) {
        $attr = array('class' => 'six') + (array) CRM_Utils_Array::value('postal_code', $attributes);
        $form->addElement('text', 'postal_code_low', NULL, $attr + array('placeholder' => ts('From')));
        $form->addElement('text', 'postal_code_high', NULL, $attr + array('placeholder' => ts('To')));
      }
    }

    // extend addresses with proximity search
    if (!empty($config->geocodeMethod)) {
      $form->addElement('text', 'prox_distance', ts('Find contacts within'), array('class' => 'six'));
      $form->addElement('select', 'prox_distance_unit', NULL, array(
        'miles' => ts('Miles'),
        'kilos' => ts('Kilometers')
      ));
      $form->addRule('prox_distance', ts('Please enter positive number as a distance'), 'numeric');
    }

    $form->addSelect('world_region', array('entity' => 'address', 'placeholder' => ts('- any -'), 'option_url' => NULL));

    // select for location type
    $locationType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $form->add('select', 'location_type', ts('Address Location'), $locationType, FALSE, array(
      'multiple' => TRUE,
      'class' => 'crm-select2',
      'placeholder' => ts('Primary'),
    ));

    // custom data extending addresses -
    $extends = array('Address');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $extends);
    if ($groupDetails) {
      $form->assign('addressGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $elementName = 'custom_' . $field['id'];
          CRM_Core_BAO_CustomField::addQuickFormElement($form,
            $elementName,
            $field['id'],
            FALSE, FALSE, TRUE
          );
        }
      }
    }
  }

  /**
   * @param $form
   */
  static function activity(&$form) {
    $form->add('hidden', 'hidden_activity', 1);
    CRM_Activity_BAO_Query::buildSearchForm($form);
  }

  /**
   * @param $form
   */
  static function changeLog(&$form) {
    $form->add('hidden', 'hidden_changeLog', 1);

    // block for change log
    $form->addElement('text', 'changed_by', ts('Modified By'), NULL);

    $dates = array(1 => ts('Added'), 2 => ts('Modified'));
    $form->addRadio('log_date', NULL, $dates, array('allowClear' => TRUE), '<br />');

    CRM_Core_Form_Date::buildDateRange($form, 'log_date', 1, '_low', '_high', ts('From'), FALSE, FALSE);
  }

  /**
   * @param $form
   */
  static function task(&$form) {
    $form->add('hidden', 'hidden_task', 1);
  }

  /**
   * @param $form
   */
  static function relationship(&$form) {
    $form->add('hidden', 'hidden_relationship', 1);

    $allRelationshipType = array();
    $allRelationshipType = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, NULL, TRUE);
    $form->add('select', 'relation_type_id', ts('Relationship Type'), array('' => ts('- select -')) + $allRelationshipType, FALSE, array('class' => 'crm-select2'));
    $form->addElement('text', 'relation_target_name', ts('Target Contact'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));
    // relation status
    $relStatusOption = array(ts('Active '), ts('Inactive '), ts('All'));
    $form->addRadio('relation_status', ts('Relationship Status'), $relStatusOption);
    $form->setDefaults(array('relation_status' => 0));
    // relation permission
    $relPermissionOption = array(ts('Any'), ts('Yes '), ts('No ') );
    $form->addRadio('relation_permission', ts('Permissioned Relationship?'), $relPermissionOption);
    $form->setDefaults(array('relation_permission' => 0));

    //add the target group
    if ($form->_group) {
      $form->add('select', 'relation_target_group', ts('Target Contact(s) in Group'), $form->_group, FALSE,
        array('id' => 'relation_target_group', 'multiple' => 'multiple', 'class' => 'crm-select2')
      );
    }
    CRM_Core_Form_Date::buildDateRange($form, 'relation_start_date', 1, '_low', '_high', ts('From:'), FALSE, FALSE);
    CRM_Core_Form_Date::buildDateRange($form, 'relation_end_date', 1, '_low', '_high', ts('From:'), FALSE, FALSE);

    // Add reltionship dates
    CRM_Core_Form_Date::buildDateRange($form, 'relation_date', 1, '_low', '_high', ts('From:'), FALSE, FALSE);

    // add all the custom  searchable fields
    $relationship = array('Relationship');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $relationship);
    if ($groupDetails) {
      $form->assign('relationshipGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $fieldId = $field['id'];
          $elementName = 'custom_' . $fieldId;
          CRM_Core_BAO_CustomField::addQuickFormElement($form,
            $elementName,
            $fieldId,
            FALSE, FALSE, TRUE
          );
        }
      }
    }
  }

  /**
   * @param $form
   */
  static function demographics(&$form) {
    $form->add('hidden', 'hidden_demographics', 1);
    // radio button for gender
    $genderOptions = array();
    $gender = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
    foreach ($gender as $key => $var) {
      $genderOptions[$key] = $form->createElement('radio', NULL,
        ts('Gender'), $var, $key,
        array('id' => "civicrm_gender_{$var}_{$key}")
      );
    }
    $form->addGroup($genderOptions, 'gender_id', ts('Gender'))->setAttribute('allowClear', TRUE);

    CRM_Core_Form_Date::buildDateRange($form, 'birth_date', 1, '_low', '_high', ts('From'), FALSE, FALSE, 'birth');

    CRM_Core_Form_Date::buildDateRange($form, 'deceased_date', 1, '_low', '_high', ts('From'), FALSE, FALSE, 'birth');


    // radio button for is_deceased
    $form->addYesNo( 'is_deceased', ts('Deceased'), TRUE);
  }

  /**
   * @param $form
   */
  static function notes(&$form) {
    $form->add('hidden', 'hidden_notes', 1);

    $options = array(
      2 => ts('Body Only'),
      3 => ts('Subject Only'),
      6 => ts('Both'),
    );
    $form->addRadio('note_option', '', $options);

    $form->addElement('text', 'note', ts('Note Text'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    $form->setDefaults(array('note_option' => 6));
  }

  /**
   * Generate the custom Data Fields based
   * on the is_searchable
   *
   * @access private
   *
   * @param $form
   *
   * @return void
   */
  static function custom(&$form) {
    $form->add('hidden', 'hidden_custom', 1);
    $extends = array_merge(array('Contact', 'Individual', 'Household', 'Organization'),
      CRM_Contact_BAO_ContactType::subTypes()
    );
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE,
      $extends
    );

    $form->assign('groupTree', $groupDetails);

    foreach ($groupDetails as $key => $group) {
      $_groupTitle[$key] = $group['name'];
      CRM_Core_ShowHideBlocks::links($form, $group['name'], '', '');

      $groupId = $group['id'];
      foreach ($group['fields'] as $field) {
        $fieldId = $field['id'];
        $elementName = 'custom_' . $fieldId;

        CRM_Core_BAO_CustomField::addQuickFormElement($form,
          $elementName,
          $fieldId,
          FALSE, FALSE, TRUE
        );
      }
    }

    //TODO: validate for only one state if prox_distance isset
  }

  /**
   * @param $form
   */
  static function CiviCase(&$form) {
    //Looks like obsolete code, since CiviCase is a component, but might be used by HRD
    $form->add('hidden', 'hidden_CiviCase', 1);
    CRM_Case_BAO_Query::buildSearchForm($form);
  }
}

