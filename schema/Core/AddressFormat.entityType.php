<?php

return [
  'name' => 'AddressFormat',
  'table' => 'civicrm_address_format',
  'class' => 'CRM_Core_DAO_AddressFormat',
  'getInfo' => fn() => [
    'title' => ts('Address Format'),
    'title_plural' => ts('Address Formats'),
    'description' => ts('Table of different address formats'),
    'add' => '3.2',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Address Format ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Address Format ID'),
      'add' => '3.2',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'format' => [
      'title' => ts('Address Format'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('The format of an address'),
      'add' => '3.2',
    ],
  ],
];
