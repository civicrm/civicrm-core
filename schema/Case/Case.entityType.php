<?php

return [
  'name' => 'Case',
  'table' => 'civicrm_case',
  'class' => 'CRM_Case_DAO_Case',
  'getInfo' => fn() => [
    'title' => ts('Case'),
    'title_plural' => ts('Cases'),
    'description' => ts('Collections of activities and relationships for a given purpose.'),
    'log' => TRUE,
    'add' => '1.8',
    'icon' => 'fa-folder-open',
    'label_field' => 'subject',
  ],
  'getPaths' => fn() => [
    'view' => 'civicrm/contact/view/case?action=view&reset=1&id=[id]',
  ],
  'getIndices' => fn() => [
    'index_case_type_id' => [
      'fields' => [
        'case_type_id' => TRUE,
      ],
      'add' => '2.0',
    ],
    'index_is_deleted' => [
      'fields' => [
        'is_deleted' => TRUE,
      ],
      'add' => '2.2',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Case ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique Case ID'),
      'add' => '1.8',
      'unique_name' => 'case_id',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'case_type_id' => [
      'title' => ts('Case Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('FK to civicrm_case_type.id'),
      'add' => '2.0',
      'usage' => [
        'import',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Case Type'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_case_type',
        'key_column' => 'id',
        'label_column' => 'title',
      ],
      'entity_reference' => [
        'entity' => 'CaseType',
        'key' => 'id',
      ],
    ],
    'subject' => [
      'title' => ts('Case Subject'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('Short name of the case.'),
      'add' => '1.8',
      'unique_name' => 'case_subject',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'start_date' => [
      'title' => ts('Case Start Date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => ts('Date on which given case starts.'),
      'add' => '1.8',
      'unique_name' => 'case_start_date',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'format_type' => 'activityDate',
      ],
    ],
    'end_date' => [
      'title' => ts('Case End Date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => ts('Date on which given case ends.'),
      'add' => '1.8',
      'unique_name' => 'case_end_date',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'format_type' => 'activityDate',
      ],
    ],
    'details' => [
      'title' => ts('Details'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Details populated from Open Case. Only used in the CiviCase extension.'),
      'add' => '1.8',
      'input_attrs' => [
        'rows' => 8,
        'cols' => 60,
        'label' => ts('Details'),
      ],
    ],
    'status_id' => [
      'title' => ts('Case Status'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('ID of case status.'),
      'add' => '1.8',
      'unique_name' => 'case_status_id',
      'usage' => [
        'import',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'control_field' => 'case_type_id',
      ],
      'pseudoconstant' => [
        'option_group_name' => 'case_status',
        'condition_provider' => ['CRM_Case_BAO_Case', 'alterStatusOptions'],
      ],
    ],
    'is_deleted' => [
      'title' => ts('Case is in the Trash'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'add' => '2.2',
      'unique_name' => 'case_deleted',
      'default' => FALSE,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'created_date' => [
      'title' => ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('When was the case was created.'),
      'add' => '4.7',
      'unique_name' => 'case_created_date',
      'default' => NULL,
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Created Date'),
      ],
    ],
    'modified_date' => [
      'title' => ts('Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('When was the case (or closely related entity) was created or modified or deleted.'),
      'add' => '4.7',
      'unique_name' => 'case_modified_date',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Modified Date'),
      ],
    ],
  ],
];
