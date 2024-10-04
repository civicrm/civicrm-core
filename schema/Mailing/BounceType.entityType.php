<?php

return [
  'name' => 'BounceType',
  'table' => 'civicrm_mailing_bounce_type',
  'class' => 'CRM_Mailing_DAO_BounceType',
  'getInfo' => fn() => [
    'title' => ts('Bounce Type'),
    'title_plural' => ts('Bounce Types'),
    'description' => ts('Table to index the various bounce types and their properties'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Bounce Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Bounce Type Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Type of bounce'),
    ],
    'description' => [
      'title' => ts('Bounce Type Description'),
      'sql_type' => 'varchar(2048)',
      'input_type' => 'Text',
      'description' => ts('A description of this bounce type'),
    ],
    'hold_threshold' => [
      'title' => ts('Hold Threshold'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Number of bounces of this type required before the email address is put on bounce hold'),
    ],
  ],
];
