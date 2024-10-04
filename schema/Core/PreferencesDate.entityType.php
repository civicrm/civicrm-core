<?php

return [
  'name' => 'PreferencesDate',
  'table' => 'civicrm_preferences_date',
  'class' => 'CRM_Core_DAO_PreferencesDate',
  'getInfo' => fn() => [
    'title' => ts('Date Preference'),
    'title_plural' => ts('Date Preferences'),
    'description' => ts('Define date preferences for the site'),
    'log' => TRUE,
    'add' => '2.0',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/admin/setting/preferences/date/edit?reset=1&action=add',
    'browse' => 'civicrm/admin/setting/preferences/date?reset=1',
    'update' => 'civicrm/admin/setting/preferences/date/edit?reset=1&action=update&id=[id]',
  ],
  'getIndices' => fn() => [
    'index_name' => [
      'fields' => [
        'name' => TRUE,
      ],
      'add' => '2.0',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Date Preference ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '2.0',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Date Preference Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('The meta name for this date (fixed in code)'),
      'add' => '2.0',
    ],
    'description' => [
      'title' => ts('Description'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Description of this date type.'),
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Description'),
      ],
    ],
    'start' => [
      'title' => ts('Start'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('The start offset relative to current year'),
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Start'),
      ],
    ],
    'end' => [
      'title' => ts('End Offset'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('The end offset relative to current year, can be negative'),
      'add' => '2.0',
    ],
    'date_format' => [
      'title' => ts('Date Format'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('The date type'),
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Date Format'),
      ],
    ],
    'time_format' => [
      'title' => ts('Time Format'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('time format'),
      'add' => '3.1',
      'input_attrs' => [
        'label' => ts('Time Format'),
      ],
    ],
  ],
];
