<?php
use CRM_Search_ExtensionUtil as E;

return [
  'name' => 'SearchDisplay',
  'table' => 'civicrm_search_display',
  'class' => 'CRM_Search_DAO_SearchDisplay',
  'getInfo' => fn() => [
    'title' => E::ts('Search Display'),
    'title_plural' => E::ts('Search Displays'),
    'description' => E::ts('SearchKit - saved search displays'),
    'log' => TRUE,
    'icon' => 'fa-clone',
    'label_field' => 'label',
  ],
  'getIndices' => fn() => [
    'UI_saved_search__id_name' => [
      'fields' => [
        'saved_search_id' => TRUE,
        'name' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '1.0',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('Search Display ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique SearchDisplay ID'),
      'add' => '1.0',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => E::ts('Search Display Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Unique name for identifying search display'),
      'add' => '1.0',
      'input_attrs' => [
        'maxlength' => 255,
      ],
    ],
    'label' => [
      'title' => E::ts('Search Display Label'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Label for identifying search display to administrators'),
      'add' => '1.0',
      'input_attrs' => [
        'maxlength' => 255,
      ],
    ],
    'saved_search_id' => [
      'title' => E::ts('Saved Search ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('FK to saved search table.'),
      'add' => '1.0',
      'entity_reference' => [
        'entity' => 'SavedSearch',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'type' => [
      'title' => E::ts('Search Display Type'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => E::ts('Type of display'),
      'add' => '1.0',
      'input_attrs' => [
        'maxlength' => 128,
      ],
      'pseudoconstant' => [
        'option_group_name' => 'search_display_type',
      ],
    ],
    'settings' => [
      'title' => E::ts('Search Display Settings'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('Configuration data for the search display'),
      'add' => '1.0',
      'default' => NULL,
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
    ],
    'acl_bypass' => [
      'title' => E::ts('Bypass ACL Permissions'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'description' => E::ts('Skip permission checks and ACLs when running this display.'),
      'add' => '5.40',
      'default' => FALSE,
    ],
  ],
];
