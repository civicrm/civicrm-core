<?php

return [
  'name' => 'ActivityContact',
  'table' => 'civicrm_activity_contact',
  'class' => 'CRM_Activity_DAO_ActivityContact',
  'getInfo' => fn() => [
    'title' => ts('Activity Contact'),
    'title_plural' => ts('Activity Contacts'),
    'description' => ts('Activity Contact'),
    'log' => TRUE,
    'add' => '4.4',
  ],
  'getIndices' => fn() => [
    'UI_activity_contact' => [
      'fields' => [
        'contact_id' => TRUE,
        'activity_id' => TRUE,
        'record_type_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '4.4',
    ],
    'index_record_type' => [
      'fields' => [
        'activity_id' => TRUE,
        'record_type_id' => TRUE,
      ],
      'add' => '4.4',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Activity Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Activity contact id'),
      'add' => '4.4',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'activity_id' => [
      'title' => ts('Activity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Foreign key to the activity for this record.'),
      'add' => '4.4',
      'input_attrs' => [
        'label' => ts('Activity'),
      ],
      'entity_reference' => [
        'entity' => 'Activity',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Foreign key to the contact for this record.'),
      'add' => '4.4',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'record_type_id' => [
      'title' => ts('Activity Contact Type'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Determines the contact\'s role in the activity (source, target, or assignee).'),
      'add' => '4.4',
      'input_attrs' => [
        'label' => ts('Contact Role'),
      ],
      'pseudoconstant' => [
        'option_group_name' => 'activity_contacts',
      ],
    ],
  ],
];
