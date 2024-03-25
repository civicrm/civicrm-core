<?php

return [
  'name' => 'LocBlock',
  'table' => 'civicrm_loc_block',
  'class' => 'CRM_Core_DAO_LocBlock',
  'getInfo' => fn() => [
    'title' => ts('Location'),
    'title_plural' => ts('Locations'),
    'description' => ts('Define location specific properties'),
    'log' => TRUE,
    'add' => '2.0',
    'icon' => 'fa-map-o',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Location Block ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique ID'),
      'add' => '2.0',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'address_id' => [
      'title' => ts('Address ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Address'),
      ],
      'entity_reference' => [
        'entity' => 'Address',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'email_id' => [
      'title' => ts('Email ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Email'),
      ],
      'entity_reference' => [
        'entity' => 'Email',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'phone_id' => [
      'title' => ts('Phone ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Phone'),
      ],
      'entity_reference' => [
        'entity' => 'Phone',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'im_id' => [
      'title' => ts('IM ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Instant Messenger'),
      ],
      'entity_reference' => [
        'entity' => 'IM',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'address_2_id' => [
      'title' => ts('Address 2 ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Address 2'),
      ],
      'entity_reference' => [
        'entity' => 'Address',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'email_2_id' => [
      'title' => ts('Email 2 ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Email 2'),
      ],
      'entity_reference' => [
        'entity' => 'Email',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'phone_2_id' => [
      'title' => ts('Phone 2 ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Phone 2'),
      ],
      'entity_reference' => [
        'entity' => 'Phone',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'im_2_id' => [
      'title' => ts('IM 2 ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Instant Messenger 2'),
      ],
      'entity_reference' => [
        'entity' => 'IM',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
  ],
];
