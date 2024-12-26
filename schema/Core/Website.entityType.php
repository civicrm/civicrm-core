<?php

return [
  'name' => 'Website',
  'table' => 'civicrm_website',
  'class' => 'CRM_Core_DAO_Website',
  'getInfo' => fn() => [
    'title' => ts('Website'),
    'title_plural' => ts('Websites'),
    'description' => ts('Website information for a specific location.'),
    'add' => '3.2',
    'icon' => 'fa-desktop',
    'label_field' => 'url',
  ],
  'getIndices' => fn() => [
    'UI_website_type_id' => [
      'fields' => [
        'website_type_id' => TRUE,
      ],
      'add' => '3.2',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Website ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique Website ID'),
      'add' => '3.2',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Contact ID'),
      'add' => '3.2',
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'url' => [
      'title' => ts('Website'),
      'sql_type' => 'varchar(2048)',
      'input_type' => 'Text',
      'description' => ts('Website'),
      'add' => '3.2',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '45',
      ],
    ],
    'website_type_id' => [
      'title' => ts('Website Type'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Which Website type does this website belong to.'),
      'add' => '3.2',
      'pseudoconstant' => [
        'option_group_name' => 'website_type',
      ],
    ],
  ],
];
