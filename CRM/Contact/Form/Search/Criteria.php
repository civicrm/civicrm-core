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
class CRM_Contact_Form_Search_Criteria {

  /**
   * @param CRM_Contact_Form_Search_Advanced $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function basic(&$form) {
    $form->addSearchFieldMetadata(['Contact' => self::getFilteredSearchFieldMetadata('basic')]);
    $form->addFormFieldsFromMetadata();
    self::setBasicSearchFields($form);
    $form->addElement('hidden', 'hidden_basic', 1);

    if ($form->_searchOptions['contactType']) {
      $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements();

      if ($contactTypes) {
        $form->add('select', 'contact_type', ts('Contact Type'), $contactTypes, FALSE,
          ['id' => 'contact_type', 'multiple' => 'multiple', 'class' => 'crm-select2', 'style' => 'width: 100%;']
        );
      }
    }

    if ($form->_searchOptions['groups']) {
      // multiselect for groups
      if ($form->_group) {
        // Arrange groups into hierarchical listing (child groups follow their parents and have indentation spacing in title)
        $groupHierarchy = CRM_Contact_BAO_Group::getGroupsHierarchy($form->_group, NULL, '- ', TRUE);

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

    // Suppress e-notices for tag fields if not set...
    $form->addOptionalQuickFormElement('tag_types_text');
    $form->addOptionalQuickFormElement('tag_set');
    $form->addOptionalQuickFormElement('all_tag_types');
    if ($form->_searchOptions['tags']) {
      // multiselect for categories
      $contactTags = CRM_Core_BAO_Tag::getTags();

      if ($contactTags) {
        $form->add('select', 'contact_tags', ts('Tag'), $contactTags, FALSE,
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
        // check if there are tags for cases or activities, if no - keep checkbox hidden on adv search
        if (count($tags) && ($key == 'civicrm_case' || $key == 'civicrm_activity')) {
          //if tags exists then add type to display in adv search form help text
          $tagsTypes[] = $value;
          $showAllTagTypes = TRUE;
        }
      }
      $tagTypesText = implode(' or ', $tagsTypes);
      if ($showAllTagTypes) {
        $form->add('checkbox', 'all_tag_types', ts('Include tags used for %1', [1 => $tagTypesText]));
        $form->add('hidden', 'tag_types_text', $tagTypesText);
      }
    }

    //added contact source
    $form->add('text', 'contact_source', ts('Contact Source'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'contact_source'));

    //added job title
    $form->addElement('text', 'job_title', ts('Job Title'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'job_title'));

    //added internal ID
    $form->add('number', 'id', ts('Contact ID'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'id') + ['min' => 1]);
    $form->addRule('id', ts('Please enter valid Contact ID'), 'positiveInteger');

    //added external ID
    $form->addElement('text', 'external_identifier', ts('External ID'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'external_identifier'));

    if (CRM_Core_Permission::check('access deleted contacts') and Civi::settings()->get('contact_undelete')) {
      $form->add('checkbox', 'deleted_contacts', ts('Search Deleted Contacts'));
    }

    // add checkbox for cms users only
    $form->addYesNo('uf_user', ts('CMS User'), TRUE);

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
      ts('Display Columns'),
      [
        '0' => ts('- default columns -'),
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
      2 => ts('Include by Privacy Option'),
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
    $locationType = CRM_Core_DAO_Address::buildOptions('location_type_id');
    $phoneType = CRM_Core_DAO_Phone::buildOptions('phone_type_id');
    $form->add('select', 'phone_location_type_id', ts('Phone Location'), ['' => ts('- any -')] + $locationType, FALSE, ['class' => 'crm-select2']);
    $form->add('select', 'phone_phone_type_id', ts('Phone Type'), ['' => ts('- any -')] + $phoneType, FALSE, ['class' => 'crm-select2']);
  }

  /**
   * Get the metadata for fields to be included on the contact search form.
   *
   * @throws \CRM_Core_Exception
   */
  public static function getSearchFieldMetadata() {
    $fields = [
      'sort_name' => [
        'title' => ts('Name'),
        'template_grouping' => 'basic',
        'template' => 'CRM/Contact/Form/Search/Criteria/Fields/sort_name.tpl',
      ],
      'first_name' => ['template_grouping' => 'basic'],
      'last_name' => ['template_grouping' => 'basic'],
      'email' => ['title' => ts('Email'), 'entity' => 'Email', 'template_grouping' => 'basic'],
      'contact_tags' => ['name' => 'contact_tags', 'type' => CRM_Utils_Type::T_INT, 'is_pseudofield' => TRUE, 'template_grouping' => 'basic'],
      'created_date' => ['name' => 'created_date', 'template_grouping' => 'changeLog'],
      'modified_date' => ['name' => 'modified_date', 'template_grouping' => 'changeLog'],
      'birth_date' => ['name' => 'birth_date', 'template_grouping' => 'demographic'],
      'deceased_date' => ['name' => 'deceased_date', 'template_grouping' => 'demographic'],
      'is_deceased' => ['is_deceased', 'template_grouping' => 'demographic'],
      'relationship_start_date' => ['name' => 'relationship_start_date', 'template_grouping' => 'relationship'],
      'relationship_end_date' => ['name' => 'relationship_end_date', 'template_grouping' => 'relationship'],
       // PseudoRelationship date field.
      'relation_active_period_date' => [
        'name' => 'relation_active_period_date',
        'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
        'title' => ts('Active Period'),
        'table_name' => 'civicrm_relationship',
        'where' => 'civicrm_relationship.start_date',
        'where_end' => 'civicrm_relationship.end_date',
        'html' => ['type' => 'SelectDate', 'formatType' => 'activityDateTime'],
        'template_grouping' => 'relationship',
      ],
    ];

    $metadata = civicrm_api3('Relationship', 'getfields', [])['values'];
    $metadata = array_merge($metadata, civicrm_api3('Contact', 'getfields', [])['values']);
    foreach ($fields as $fieldName => $field) {
      $fields[$fieldName] = array_merge($metadata[$fieldName] ?? [], $field);
    }
    return $fields;
  }

  /**
   * Get search field metadata filtered by the template grouping field.
   *
   * @param string $filter
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getFilteredSearchFieldMetadata($filter) {
    $fields = self::getSearchFieldMetadata();
    foreach ($fields as $index => $field) {
      if ($field['template_grouping'] !== $filter) {
        unset($fields[$index]);
      }
    }
    return $fields;
  }

  /**
   * Defines the fields that can be displayed for the basic search section.
   *
   * @param CRM_Core_Form $form
   *
   * @throws \CRM_Core_Exception
   */
  protected static function setBasicSearchFields($form) {
    $searchFields = [];
    foreach (self::getFilteredSearchFieldMetadata('basic') as $fieldName => $field) {
      $searchFields[$fieldName] = $field;
    }
    $fields = array_merge(self::getBasicSearchFields(), $searchFields);
    foreach ($fields as $index => $field) {
      $fields[$index] = array_merge(['class' => '', 'is_custom' => FALSE, 'template' => '', 'help' => '', 'description' => ''], $field);
    }
    $form->assign('basicSearchFields', $fields);
  }

  /**
   * Return list of basic contact fields that can be displayed for the basic search section.
   *
   */
  public static function getBasicSearchFields() {
    return [
      // For now an empty array is still left in place for ordering.
      'sort_name' => [],
      'first_name' => [],
      'last_name' => [],
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
        'help' => ['id' => 'id-all-tags', 'file' => NULL],
      ],
      'tag_set' => [
        'name' => 'tag_set',
        'is_custom' => TRUE,
        'template' => 'CRM/Contact/Form/Search/Criteria/Fields/tag_set.tpl',
      ],
      'all_tag_types' => [
        'name' => 'all_tag_types',
        'class' => 'search-field__span-3 search-field__checkbox',
        'help' => ['id' => 'id-all-tag-types', 'file' => NULL],
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
        'name' => 'id',
        'help' => ['id' => 'id-contact-id', 'file' => 'CRM/Contact/Form/Contact'],
      ],
      'external_identifier' => [
        'name' => 'external_identifier',
        'help' => ['id' => 'id-external-id', 'file' => 'CRM/Contact/Form/Contact'],
      ],
      'uf_user' => [
        'name' => 'uf_user',
      ],
    ];
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

    $parseStreetAddress = $addressOptions['street_address_parsing'] ?? 0;
    $form->assign('parseStreetAddress', $parseStreetAddress);
    foreach ($elements as $name => $v) {
      [$title, $attributes, $select, $multiSelect] = $v;

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
        $attr = ['class' => 'six'] + ($attributes['postal_code'] ?? []);
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
    $locationType = CRM_Core_DAO_Address::buildOptions('location_type_id');
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
   *
   * @throws \CRM_Core_Exception
   */
  public static function changeLog(&$form) {
    $form->add('hidden', 'hidden_changeLog', 1);
    $form->addSearchFieldMetadata(['Contact' => self::getFilteredSearchFieldMetadata('changeLog')]);
    $form->addFormFieldsFromMetadata();
    // block for change log
    $form->addElement('text', 'changed_by', ts('Modified By'), NULL);
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function task(&$form) {
    $form->add('hidden', 'hidden_task', 1);
  }

  /**
   * @param CRM_Core_Form_Search $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function relationship(&$form) {
    $form->add('hidden', 'hidden_relationship', 1);
    $form->addSearchFieldMetadata(['Relationship' => self::getFilteredSearchFieldMetadata('relationship')]);
    $form->addFormFieldsFromMetadata();
    $form->add('text', 'relation_description', ts('Description'), ['class' => 'twenty']);
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

    // add all the custom  searchable fields
    CRM_Core_BAO_Query::addCustomFormFields($form, ['Relationship']);
  }

  /**
   * @param CRM_Core_Form_Search $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function demographics(&$form) {
    $form->add('hidden', 'hidden_demographics', 1);
    $form->addSearchFieldMetadata(['Contact' => self::getFilteredSearchFieldMetadata('demographic')]);
    $form->addFormFieldsFromMetadata();
    // radio button for gender
    $genderOptionsAttributes = [];
    $gender = CRM_Contact_DAO_Contact::buildOptions('gender_id');
    foreach ($gender as $key => $var) {
      $genderOptionsAttributes[$key] = ['id' => "civicrm_gender_{$var}_{$key}"];
    }
    $form->addRadio('gender_id', ts('Gender'), $gender, ['allowClear' => TRUE], NULL, FALSE, $genderOptionsAttributes);

    $form->add('number', 'age_low', ts('Min Age'), ['class' => 'four', 'min' => 0]);
    $form->addRule('age_low', ts('Please enter a positive integer'), 'positiveInteger');
    $form->add('number', 'age_high', ts('Max Age'), ['class' => 'four', 'min' => 0]);
    $form->addRule('age_high', ts('Please enter a positive integer'), 'positiveInteger');
    $form->add('datepicker', 'age_asof_date', ts('As of'), NULL, FALSE, ['time' => FALSE]);
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
   *
   * @throws \CRM_Core_Exception
   */
  public static function custom(&$form) {
    $form->add('hidden', 'hidden_custom', 1);
    $groupDetails = CRM_Core_BAO_CustomGroup::getAll(['extends' => 'Contact', 'is_active' => TRUE]);
    $form->assign('groupTree', $groupDetails);

    foreach ($groupDetails as $key => $group) {
      $_groupTitle[$key] = $group['name'];

      foreach ($group['fields'] as $field) {
        $fieldId = $field['id'];
        $elementName = 'custom_' . $fieldId;
        if ($field['data_type'] === 'Date' && $field['is_search_range']) {
          $form->addDatePickerRange($elementName, $field['label']);
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
