<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Contact_Form_Search_Criteria {

  /**
   * @param CRM_Core_Form $form
   */
  public static function basic(&$form) {
    self::setBasicSearchFields($form);
    $form->addElement('hidden', 'hidden_basic', 1);

    if ($form->_searchOptions['contactType']) {
      $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements();

      if ($contactTypes) {
        $form->add('select', 'contact_type', ts('Contact Type(s)'), $contactTypes, FALSE,
          ['id' => 'contact_type', 'multiple' => 'multiple', 'class' => 'crm-select2', 'style' => 'width: 100%;']
        );
      }
    }

    if ($form->_searchOptions['groups']) {
      // multiselect for groups
      if ($form->_group) {
        // Arrange groups into hierarchical listing (child groups follow their parents and have indentation spacing in title)
        $groupHierarchy = CRM_Contact_BAO_Group::getGroupsHierarchy($form->_group, NULL, '&nbsp;&nbsp;', TRUE);

        $form->add('select', 'group', ts('Groups'), $groupHierarchy, FALSE,
          ['id' => 'group', 'multiple' => 'multiple', 'class' => 'crm-select2']
        );
        $groupOptions = CRM_Core_BAO_OptionValue::getOptionValuesAssocArrayFromName('group_type');
        $form->add('select', 'group_type', ts('Group Types'), $groupOptions, FALSE,
          ['id' => 'group_type', 'multiple' => 'multiple', 'class' => 'crm-select2']
        );
        $form->add('hidden', 'group_search_selected', 'group');
      }
    }

    if ($form->_searchOptions['tags']) {
      // multiselect for categories
      $contactTags = CRM_Core_BAO_Tag::getTags();

      if ($contactTags) {
        $form->add('select', 'contact_tags', ts('Select Tag(s)'), $contactTags, FALSE,
          ['id' => 'contact_tags', 'multiple' => 'multiple', 'class' => 'crm-select2', 'style' => 'width: 100%;']
        );
      }

      $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_contact');
      CRM_Core_Form_Tag::buildQuickForm($form, $parentNames, 'civicrm_contact', NULL, TRUE, FALSE);

      $used_for = CRM_Core_OptionGroup::values('tag_used_for');
      $tagsTypes = [];
      $showAllTagTypes = FALSE;
      foreach ($used_for as $key => $value) {
        //check tags for every type and find if there are any defined
        $tags = CRM_Core_BAO_Tag::getTagsUsedFor($key, FALSE, TRUE, NULL);
        // check if there are tags other than contact type, if no - keep checkbox hidden on adv search
        // we will hide searching contact by attachments tags until it will be implemented in core
        if (count($tags) && $key != 'civicrm_file' && $key != 'civicrm_contact') {
          //if tags exists then add type to display in adv search form help text
          $tagsTypes[] = ts($value);
          $showAllTagTypes = TRUE;
        }
      }
      $tagTypesText = implode(" or ", $tagsTypes);
      if ($showAllTagTypes) {
        $form->add('checkbox', 'all_tag_types', ts('Include tags used for %1', [1 => $tagTypesText]));
        $form->add('hidden', 'tag_types_text', $tagTypesText);
      }
    }

    // add text box for last name, first name, street name, city
    $form->addElement('text', 'sort_name', ts('Complete OR Partial Name'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    // add text box for last name, first name, street name, city
    $form->add('text', 'email', ts('Complete OR Partial Email'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    //added contact source
    $form->add('text', 'contact_source', ts('Contact Source'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'contact_source'));

    //added job title
    $form->addElement('text', 'job_title', ts('Job Title'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'job_title'));

    //added internal ID
    $form->add('number', 'contact_id', ts('Contact ID'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'id') + ['min' => 1]);
    $form->addRule('contact_id', ts('Please enter valid Contact ID'), 'positiveInteger');

    //added external ID
    $form->addElement('text', 'external_identifier', ts('External ID'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'external_identifier'));

    if (CRM_Core_Permission::check('access deleted contacts') and Civi::settings()->get('contact_undelete')) {
      $form->add('checkbox', 'deleted_contacts', ts('Search in Trash') . '<br />' . ts('(deleted contacts)'));
    }

    // add checkbox for cms users only
    $form->addYesNo('uf_user', ts('CMS User?'), TRUE);

    // tag all search
    $form->add('text', 'tag_search', ts('All Tags'));

    // add search profiles

    // FIXME: This is probably a part of profiles - need to be
    // FIXME: eradicated from here when profiles are reworked.
    $types = ['Participant', 'Contribution', 'Membership'];

    // get component profiles
    $componentProfiles = [];
    $componentProfiles = CRM_Core_BAO_UFGroup::getProfiles($types);

    $ufGroups = CRM_Core_BAO_UFGroup::getModuleUFGroup('Search Profile', 1);
    $accessibleUfGroups = CRM_Core_Permission::ufGroup(CRM_Core_Permission::VIEW);

    $searchProfiles = [];
    foreach ($ufGroups as $key => $var) {
      if (!array_key_exists($key, $componentProfiles) && in_array($key, $accessibleUfGroups)) {
        $searchProfiles[$key] = $var['title'];
      }
    }

    $form->add('select',
      'uf_group_id',
      ts('Views For Display Contacts'),
      [
        '0' => ts('- default view -'),
      ] + $searchProfiles,
      FALSE,
      ['class' => 'crm-select2']
    );

    $componentModes = CRM_Contact_Form_Search::getModeSelect();
    $form->assign('component_mappings', json_encode(CRM_Contact_Form_Search::getModeToComponentMapping()));
    if (count($componentModes) > 1) {
      $form->add('select',
        'component_mode',
        ts('Display Results As'),
        $componentModes,
        FALSE,
        ['class' => 'crm-select2']
      );
    }

    $form->addRadio(
      'operator',
      ts('Search Operator'),
      [
        CRM_Contact_BAO_Query::SEARCH_OPERATOR_AND => ts('AND'),
        CRM_Contact_BAO_Query::SEARCH_OPERATOR_OR => ts('OR'),
      ],
      ['allowClear' => FALSE]
    );

    // add the option to display relationships
    $rTypes = CRM_Core_PseudoConstant::relationshipType();
    $rSelect = ['' => ts('- Select Relationship Type-')];
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
      $rSelect,
      ['class' => 'crm-select2']
    );

    // checkboxes for DO NOT phone, email, mail
    // we take labels from SelectValues
    $t = CRM_Core_SelectValues::privacy();
    $form->add('select',
      'privacy_options',
      ts('Privacy'),
      $t,
      FALSE,
      [
        'id' => 'privacy_options',
        'multiple' => 'multiple',
        'class' => 'crm-select2',
      ]
    );

    $form->addElement('select',
      'privacy_operator',
      ts('Operator'),
      [
        'OR' => ts('OR'),
        'AND' => ts('AND'),
      ]
    );

    $options = [
      1 => ts('Exclude'),
      2 => ts('Include by Privacy Option(s)'),
    ];
    $form->addRadio('privacy_toggle', ts('Privacy Options'), $options, ['allowClear' => FALSE]);

    // preferred communication method
    if (Civi::settings()->get('civimail_multiple_bulk_emails')) {
      $form->addSelect('email_on_hold',
        ['entity' => 'email', 'multiple' => 'multiple', 'label' => ts('Email On Hold'), 'options' => CRM_Core_PseudoConstant::emailOnHoldOptions()]);
    }
    else {
      $form->add('advcheckbox', 'email_on_hold', ts('Email On Hold'));
    }

    $form->addSelect('preferred_communication_method',
      ['entity' => 'contact', 'multiple' => 'multiple', 'label' => ts('Preferred Communication Method'), 'option_url' => NULL, 'placeholder' => ts('- any -')]);

    //CRM-6138 Preferred Language
    $form->addSelect('preferred_language', ['class' => 'twenty', 'context' => 'search']);

    // Phone search
    $form->addElement('text', 'phone_numeric', ts('Phone'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone', 'phone'));
    $locationType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $phoneType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $form->add('select', 'phone_location_type_id', ts('Phone Location'), ['' => ts('- any -')] + $locationType, FALSE, ['class' => 'crm-select2']);
    $form->add('select', 'phone_phone_type_id', ts('Phone Type'), ['' => ts('- any -')] + $phoneType, FALSE, ['class' => 'crm-select2']);
  }

  /**
   * Defines the fields that can be displayed for the basic search section.
   *
   * @param CRM_Core_Form $form
   */
  protected static function setBasicSearchFields($form) {
    $userFramework = CRM_Core_Config::singleton()->userFramework;

    $form->assign('basicSearchFields', [
      'sort_name' => ['name' => 'sort_name'],
      'email' => ['name' => 'email'],
      'contact_type' => ['name' => 'contact_type'],
      'group' => [
        'name' => 'group',
        'template' => 'CRM/Contact/Form/Search/Criteria/Fields/group.tpl',
      ],
      'contact_tags' => ['name' => 'contact_tags'],
      'tag_types_text' => ['name' => 'tag_types_text'],
      'tag_search' => [
        'name' => 'tag_search',
        'help' => ['id' => 'id-all-tags'],
      ],
      'tag_set' => [
        'name' => 'tag_set',
        'is_custom' => TRUE,
        'template' => 'CRM/Contact/Form/Search/Criteria/Fields/tag_set.tpl',
      ],
      'all_tag_types' => [
        'name' => 'all_tag_types',
        'class' => 'search-field__span-3 search-field__checkbox',
        'help' => ['id' => 'id-all-tag-types'],
      ],
      'phone_numeric' => [
        'name' => 'phone_numeric',
        'description' => ts('Punctuation and spaces are ignored.'),
      ],
      'phone_location_type_id' => ['name' => 'phone_location_type_id'],
      'phone_phone_type_id' => ['name' => 'phone_phone_type_id'],
      'privacy_toggle' => [
        'name' => 'privacy_toggle',
        'class' => 'search-field__span-2',
        'template' => 'CRM/Contact/Form/Search/Criteria/Fields/privacy_toggle.tpl',
      ],
      'preferred_communication_method' => [
        'name' => 'preferred_communication_method',
        'template' => 'CRM/Contact/Form/Search/Criteria/Fields/preferred_communication_method.tpl',
      ],
      'contact_source' => [
        'name' => 'contact_source',
        'help' => ['id' => 'id-source', 'file' => 'CRM/Contact/Form/Contact'],
      ],
      'job_title' => ['name' => 'job_title'],
      'preferred_language' => ['name' => 'preferred_language'],
      'contact_id' => [
        'name' => 'contact_id',
        'help' => ['id' => 'id-contact-id', 'file' => 'CRM/Contact/Form/Contact'],
      ],
      'external_identifier' => [
        'name' => 'external_identifier',
        'help' => ['id' => 'id-external-id', 'file' => 'CRM/Contact/Form/Contact'],
      ],
      'uf_user' => [
        'name' => 'uf_user',
        'description' => ts('Does the contact have a %1 Account?', [$userFramework]),
      ],
    ]);
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function location(&$form) {
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

    $elements = [
      'street_address' => [ts('Street Address'), $attributes['street_address'], NULL, NULL],
      'supplemental_address_1' => [ts('Supplemental Address 1'), $attributes['supplemental_address_1'], NULL, NULL],
      'supplemental_address_2' => [ts('Supplemental Address 2'), $attributes['supplemental_address_2'], NULL, NULL],
      'supplemental_address_3' => [ts('Supplemental Address 3'), $attributes['supplemental_address_3'], NULL, NULL],
      'city' => [ts('City'), $attributes['city'], NULL, NULL],
      'postal_code' => [ts('Postal Code'), $attributes['postal_code'], NULL, NULL],
      'country' => [ts('Country'), $attributes['country_id'], 'country', FALSE],
      'state_province' => [ts('State/Province'), $attributes['state_province_id'], 'stateProvince', TRUE],
      'county' => [ts('County'), $attributes['county_id'], 'county', TRUE],
      'address_name' => [ts('Address Name'), $attributes['address_name'], NULL, NULL],
      'street_number' => [ts('Street Number'), $attributes['street_number'], NULL, NULL],
      'street_name' => [ts('Street Name'), $attributes['street_name'], NULL, NULL],
      'street_unit' => [ts('Apt/Unit/Suite'), $attributes['street_unit'], NULL, NULL],
    ];

    $parseStreetAddress = CRM_Utils_Array::value('street_address_parsing', $addressOptions, 0);
    $form->assign('parseStreetAddress', $parseStreetAddress);
    foreach ($elements as $name => $v) {
      list($title, $attributes, $select, $multiSelect) = $v;

      if (in_array($name,
        ['street_number', 'street_name', 'street_unit']
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
          $selectElements = ['' => ts('- any -')] + CRM_Core_PseudoConstant::$select();
          $element = $form->add('select', $name, $title, $selectElements, FALSE, ['class' => 'crm-select2']);
        }
        if ($multiSelect) {
          $element->setMultiple(TRUE);
        }
      }
      else {
        $form->addElement('text', $name, $title, $attributes);
      }

      if ($addressOptions['postal_code']) {
        $attr = ['class' => 'six'] + (array) CRM_Utils_Array::value('postal_code', $attributes);
        $form->addElement('text', 'postal_code_low', NULL, $attr + ['placeholder' => ts('From')]);
        $form->addElement('text', 'postal_code_high', NULL, $attr + ['placeholder' => ts('To')]);
      }
    }

    // extend addresses with proximity search
    if (CRM_Utils_GeocodeProvider::getUsableClassName()) {
      $form->addElement('text', 'prox_distance', ts('Find contacts within'), ['class' => 'six']);
      $form->addElement('select', 'prox_distance_unit', NULL, [
        'miles' => ts('Miles'),
        'kilos' => ts('Kilometers'),
      ]);
      $form->addRule('prox_distance', ts('Please enter positive number as a distance'), 'numeric');
    }

    $form->addSelect('world_region', ['entity' => 'address', 'context' => 'search']);

    // select for location type
    $locationType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $form->add('select', 'location_type', ts('Address Location'), $locationType, FALSE, [
      'multiple' => TRUE,
      'class' => 'crm-select2',
      'placeholder' => ts('Primary'),
    ]);

    // custom data extending addresses
    CRM_Core_BAO_Query::addCustomFormFields($form, ['Address']);
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function activity(&$form) {
    $form->add('hidden', 'hidden_activity', 1);
    CRM_Activity_BAO_Query::buildSearchForm($form);
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function changeLog(&$form) {
    $form->add('hidden', 'hidden_changeLog', 1);

    // block for change log
    $form->addElement('text', 'changed_by', ts('Modified By'), NULL);

    $dates = [1 => ts('Added'), 2 => ts('Modified')];
    $form->addRadio('log_date', NULL, $dates, ['allowClear' => TRUE]);

    CRM_Core_Form_Date::buildDateRange($form, 'log_date', 1, '_low', '_high', ts('From:'), FALSE, FALSE);
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function task(&$form) {
    $form->add('hidden', 'hidden_task', 1);
  }

  /**
   * @param $form
   */
  public static function relationship(&$form) {
    $form->add('hidden', 'hidden_relationship', 1);

    $allRelationshipType = [];
    $allRelationshipType = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, NULL, TRUE);
    $form->add('select', 'relation_type_id', ts('Relationship Type'), ['' => ts('- select -')] + $allRelationshipType, FALSE, ['multiple' => TRUE, 'class' => 'crm-select2']);
    $form->addElement('text', 'relation_target_name', ts('Target Contact'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));
    // relation status
    $relStatusOption = [ts('Active'), ts('Inactive'), ts('All')];
    $form->addRadio('relation_status', ts('Relationship Status'), $relStatusOption);
    $form->setDefaults(['relation_status' => 0]);
    // relation permission
    $allRelationshipPermissions = CRM_Contact_BAO_Relationship::buildOptions('is_permission_a_b');
    $form->add('select', 'relation_permission', ts('Permissioned Relationship'),
     ['' => ts('- select -')] + $allRelationshipPermissions, FALSE, ['multiple' => TRUE, 'class' => 'crm-select2']);

    //add the target group
    if ($form->_group) {
      $form->add('select', 'relation_target_group', ts('Target Contact(s) in Group'), $form->_group, FALSE,
        ['id' => 'relation_target_group', 'multiple' => 'multiple', 'class' => 'crm-select2']
      );
    }
    CRM_Core_Form_Date::buildDateRange($form, 'relation_start_date', 1, '_low', '_high', ts('From:'), FALSE, FALSE);
    CRM_Core_Form_Date::buildDateRange($form, 'relation_end_date', 1, '_low', '_high', ts('From:'), FALSE, FALSE);

    CRM_Core_Form_Date::buildDateRange($form, 'relation_active_period_date', 1, '_low', '_high', ts('From:'), FALSE, FALSE);

    // Add reltionship dates
    CRM_Core_Form_Date::buildDateRange($form, 'relation_date', 1, '_low', '_high', ts('From:'), FALSE, FALSE);

    // add all the custom  searchable fields
    CRM_Core_BAO_Query::addCustomFormFields($form, ['Relationship']);
  }

  /**
   * @param CRM_Core_Form_Search $form
   */
  public static function demographics(&$form) {
    $form->add('hidden', 'hidden_demographics', 1);
    // radio button for gender
    $genderOptions = [];
    $gender = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
    foreach ($gender as $key => $var) {
      $genderOptions[$key] = $form->createElement('radio', NULL,
        ts('Gender'), $var, $key,
        ['id' => "civicrm_gender_{$var}_{$key}"]
      );
    }
    $form->addGroup($genderOptions, 'gender_id', ts('Gender'))->setAttribute('allowClear', TRUE);

    $form->add('number', 'age_low', ts('Min Age'), ['class' => 'four', 'min' => 0]);
    $form->addRule('age_low', ts('Please enter a positive integer'), 'positiveInteger');
    $form->add('number', 'age_high', ts('Max Age'), ['class' => 'four', 'min' => 0]);
    $form->addRule('age_high', ts('Please enter a positive integer'), 'positiveInteger');
    $form->add('datepicker', 'age_asof_date', ts('As of'), NULL, FALSE, ['time' => FALSE]);

    CRM_Core_Form_Date::buildDateRange($form, 'birth_date', 1, '_low', '_high', ts('From'), FALSE, FALSE, 'birth');

    CRM_Core_Form_Date::buildDateRange($form, 'deceased_date', 1, '_low', '_high', ts('From'), FALSE, FALSE, 'birth');

    // radio button for is_deceased
    $form->addYesNo('is_deceased', ts('Deceased'), TRUE);
  }

  /**
   * @param $form
   */
  public static function notes(&$form) {
    $form->add('hidden', 'hidden_notes', 1);

    $options = [
      2 => ts('Body Only'),
      3 => ts('Subject Only'),
      6 => ts('Both'),
    ];
    $form->addRadio('note_option', '', $options);

    $form->addElement('text', 'note', ts('Note Text'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    $form->setDefaults(['note_option' => 6]);
  }

  /**
   * Generate the custom Data Fields based for those with is_searchable = 1.
   *
   * @param CRM_Contact_Form_Search $form
   */
  public static function custom(&$form) {
    $form->add('hidden', 'hidden_custom', 1);
    $extends = array_merge(['Contact', 'Individual', 'Household', 'Organization'],
      CRM_Contact_BAO_ContactType::subTypes()
    );
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE,
      $extends
    );

    $form->assign('groupTree', $groupDetails);

    foreach ($groupDetails as $key => $group) {
      $_groupTitle[$key] = $group['name'];
      CRM_Core_ShowHideBlocks::links($form, $group['name'], '', '');

      foreach ($group['fields'] as $field) {
        $fieldId = $field['id'];
        $elementName = 'custom_' . $fieldId;
        if ($field['data_type'] == 'Date' && $field['is_search_range']) {
          CRM_Core_Form_Date::buildDateRange($form, $elementName, 1, '_from', '_to', ts('From:'), FALSE);
        }
        else {
          CRM_Core_BAO_CustomField::addQuickFormElement($form, $elementName, $fieldId, FALSE, TRUE);
        }
      }
    }
  }

  /**
   * @param $form
   */
  public static function CiviCase(&$form) {
    //Looks like obsolete code, since CiviCase is a component, but might be used by HRD
    $form->add('hidden', 'hidden_CiviCase', 1);
    CRM_Case_BAO_Query::buildSearchForm($form);
  }

}
