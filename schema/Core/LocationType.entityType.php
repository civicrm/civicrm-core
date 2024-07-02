<?php

return [
  'name' => 'LocationType',
  'table' => 'civicrm_location_type',
  'class' => 'CRM_Core_DAO_LocationType',
  'getInfo' => fn() => [
    'title' => ts('Location Type'),
    'title_plural' => ts('Location Types'),
    'description' => ts('Location types that are available for address, email, phone etc.'),
    'log' => TRUE,
    'add' => '1.1',
    'label_field' => 'display_name',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/admin/locationType/edit?action=add&reset=1',
    'update' => 'civicrm/admin/locationType/edit?action=update&id=[id]&reset=1',
    'delete' => 'civicrm/admin/locationType/edit?action=delete&id=[id]&reset=1',
    'browse' => 'civicrm/admin/locationType',
  ],
  'getIndices' => fn() => [
    'UI_name' => [
      'fields' => [
        'name' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '2.1',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Location Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Location Type ID'),
      'add' => '1.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Location Type'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Location Type Name.'),
      'add' => '1.1',
    ],
    'display_name' => [
      'title' => ts('Display Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'localizable' => TRUE,
      'description' => ts('Location Type Display Name.'),
      'add' => '4.1',
    ],
    'vcard_name' => [
      'title' => ts('vCard Location Type'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('vCard Location Type Name.'),
      'add' => '1.1',
    ],
    'description' => [
      'title' => ts('Description'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Location Type Description.'),
      'add' => '1.1',
    ],
    'is_reserved' => [
      'title' => ts('Location Type is Reserved?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this location type a predefined system location?'),
      'add' => '1.1',
      'default' => FALSE,
      'input_attrs' => [
        'label' => ts('Reserved'),
      ],
    ],
    'is_active' => [
      'title' => ts('Location Type is Active?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this property active?'),
      'add' => '1.1',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'is_default' => [
      'title' => ts('Default Location Type?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this location type the default?'),
      'add' => '1.1',
      'default' => FALSE,
      'input_attrs' => [
        'label' => ts('Default'),
      ],
    ],
  ],
];
