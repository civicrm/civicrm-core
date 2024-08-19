<?php

return [
  'name' => 'Timezone',
  'table' => 'civicrm_timezone',
  'class' => 'CRM_Core_DAO_Timezone',
  'getInfo' => fn() => [
    'title' => ts('Timezone'),
    'title_plural' => ts('Timezones'),
    'description' => ts('Table containing a list of known timezones'),
    'add' => '1.8',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Timezone ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Timezone ID'),
      'add' => '1.8',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Timezone Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Timezone full name'),
      'add' => '1.8',
    ],
    'abbreviation' => [
      'title' => ts('Timezone Abbreviation'),
      'sql_type' => 'char(3)',
      'input_type' => 'Text',
      'description' => ts('ISO Code for timezone abbreviation'),
      'add' => '1.8',
    ],
    'gmt' => [
      'title' => ts('GMT Name of Timezone'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('GMT name of the timezone'),
      'add' => '1.8',
    ],
    'offset' => [
      'title' => ts('GMT Offset'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'add' => '1.8',
    ],
    'country_id' => [
      'title' => ts('Country ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Country ID'),
      'add' => '1.8',
      'input_attrs' => [
        'label' => ts('Country'),
      ],
      'entity_reference' => [
        'entity' => 'Country',
        'key' => 'id',
      ],
    ],
  ],
];
