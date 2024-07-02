<?php

return [
  'name' => 'ContactType',
  'table' => 'civicrm_contact_type',
  'class' => 'CRM_Contact_DAO_ContactType',
  'getInfo' => fn() => [
    'title' => ts('Contact Type'),
    'title_plural' => ts('Contact Types'),
    'description' => ts('Provide type information for contacts'),
    'add' => '3.1',
    'label_field' => 'label',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/admin/options/subtype/edit?action=add&reset=1',
    'update' => 'civicrm/admin/options/subtype/edit?action=update&id=[id]&reset=1',
    'delete' => 'civicrm/admin/options/subtype/edit?action=delete&id=[id]&reset=1',
    'browse' => 'civicrm/admin/options/subtype',
  ],
  'getIndices' => fn() => [
    'contact_type' => [
      'fields' => [
        'name' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '3.1',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Contact Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Contact Type ID'),
      'add' => '1.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Internal name of Contact Type (or Subtype).'),
      'add' => '3.1',
      'input_attrs' => [
        'label' => ts('Name'),
      ],
    ],
    'label' => [
      'title' => ts('Contact Type Label'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'localizable' => TRUE,
      'description' => ts('localized Name of Contact Type.'),
      'add' => '3.1',
      'input_attrs' => [
        'label' => ts('Label'),
      ],
    ],
    'description' => [
      'title' => ts('Contact Type Description'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'localizable' => TRUE,
      'description' => ts('localized Optional verbose description of the type.'),
      'add' => '3.1',
      'input_attrs' => [
        'rows' => 2,
        'cols' => 60,
      ],
    ],
    'image_URL' => [
      'title' => ts('Contact Type Image URL'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('URL of image if any.'),
      'add' => '3.1',
    ],
    'icon' => [
      'title' => ts('Icon'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('crm-i icon class representing this contact type'),
      'add' => '5.49',
      'default' => NULL,
    ],
    'parent_id' => [
      'title' => ts('Parent ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Optional FK to parent contact type.'),
      'add' => '3.1',
      'input_attrs' => [
        'label' => ts('Parent'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_contact_type',
        'key_column' => 'id',
        'label_column' => 'label',
        'condition' => 'parent_id IS NULL',
      ],
      'entity_reference' => [
        'entity' => 'ContactType',
        'key' => 'id',
      ],
    ],
    'is_active' => [
      'title' => ts('Contact Type Enabled'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this entry active?'),
      'add' => '3.1',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'is_reserved' => [
      'title' => ts('Contact Type is Reserved'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this contact type a predefined system type'),
      'add' => '3.1',
      'default' => FALSE,
    ],
  ],
];
