<?php

return [
  'name' => 'SmsProvider',
  'table' => 'civicrm_sms_provider',
  'class' => 'CRM_SMS_DAO_SmsProvider',
  'getInfo' => fn() => [
    'title' => ts('SMS Provider'),
    'title_plural' => ts('SMS Providers'),
    'description' => ts('Table to add different sms providers'),
    'add' => '4.2',
    'label_field' => 'title',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/admin/sms/provider/edit?reset=1&action=add',
    'delete' => 'civicrm/admin/sms/provider/edit?reset=1&action=delete&id=[id]',
    'update' => 'civicrm/admin/sms/provider/edit?reset=1&action=update&id=[id]',
    'browse' => 'civicrm/admin/sms/provider?reset=1',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('SMS Provider ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('SMS Provider ID'),
      'add' => '4.2',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('SMS Provider Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Provider internal name points to option_value of option_group sms_provider_name'),
      'add' => '4.2',
    ],
    'title' => [
      'title' => ts('SMS Provider Title'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Provider name visible to user'),
      'add' => '4.2',
    ],
    'username' => [
      'title' => ts('SMS Provider Username'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'add' => '4.2',
    ],
    'password' => [
      'title' => ts('SMS Provider Password'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'add' => '4.2',
    ],
    'api_type' => [
      'title' => ts('SMS Provider API'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('points to value in civicrm_option_value for group sms_api_type'),
      'add' => '4.2',
      'pseudoconstant' => [
        'option_group_name' => 'sms_api_type',
      ],
    ],
    'api_url' => [
      'title' => ts('SMS Provider API URL'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'add' => '4.2',
    ],
    'api_params' => [
      'title' => ts('SMS Provider API Params'),
      'sql_type' => 'text',
      'input_type' => 'Text',
      'description' => ts('the api params in xml, http or smtp format'),
      'add' => '4.2',
    ],
    'is_default' => [
      'title' => ts('SMS Provider is Default?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'add' => '4.2',
      'default' => FALSE,
      'input_attrs' => [
        'label' => ts('Default'),
      ],
    ],
    'is_active' => [
      'title' => ts('SMS Provider is Active?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'add' => '4.2',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'domain_id' => [
      'title' => ts('Domain ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Which Domain is this sms provider for'),
      'add' => '4.7',
      'input_attrs' => [
        'label' => ts('Domain'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_domain',
        'key_column' => 'id',
        'label_column' => 'name',
      ],
      'entity_reference' => [
        'entity' => 'Domain',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
  ],
];
