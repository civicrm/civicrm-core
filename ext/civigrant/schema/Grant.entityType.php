<?php
use CRM_Grant_ExtensionUtil as E;

return [
  'name' => 'Grant',
  'table' => 'civicrm_grant',
  'class' => 'CRM_Grant_DAO_Grant',
  'getInfo' => fn() => [
    'title' => E::ts('Grant'),
    'title_plural' => E::ts('Grants'),
    'description' => E::ts('Funds applied for and given out by this organization.'),
    'log' => TRUE,
    'add' => '1.8',
    'icon' => 'fa-money',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/grant/add?reset=1&action=add&cid=[contact_id]',
    'view' => 'civicrm/grant/view?reset=1&action=view&id=[id]',
    'update' => 'civicrm/grant/add?reset=1&action=update&id=[id]',
    'delete' => 'civicrm/grant/add?reset=1&action=delete&id=[id]',
  ],
  'getIndices' => fn() => [
    'index_grant_type_id' => [
      'fields' => [
        'grant_type_id' => TRUE,
      ],
      'add' => '1.8',
    ],
    'index_status_id' => [
      'fields' => [
        'status_id' => TRUE,
      ],
      'add' => '1.8',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('Grant ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique Grant id'),
      'add' => '1.8',
      'unique_name' => 'grant_id',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'contact_id' => [
      'title' => E::ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('Contact ID of contact record given grant belongs to.'),
      'add' => '1.8',
      'unique_name' => 'grant_contact_id',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => E::ts('Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'application_received_date' => [
      'title' => E::ts('Application received date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => E::ts('Date on which grant application was received by donor.'),
      'add' => '1.8',
      'unique_name' => 'grant_application_received_date',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'format_type' => 'activityDate',
      ],
    ],
    'decision_date' => [
      'title' => E::ts('Decision date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => E::ts('Date on which grant decision was made.'),
      'add' => '1.8',
      'unique_name' => 'grant_decision_date',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'format_type' => 'activityDate',
      ],
    ],
    'money_transfer_date' => [
      'title' => E::ts('Grant Money transfer date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => E::ts('Date on which grant money transfer was made.'),
      'add' => '1.8',
      'unique_name' => 'grant_money_transfer_date',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'format_type' => 'activityDate',
      ],
    ],
    'grant_due_date' => [
      'title' => E::ts('Grant Report Due Date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => E::ts('Date on which grant report is due.'),
      'add' => '1.8',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'format_type' => 'activityDate',
      ],
    ],
    'grant_report_received' => [
      'title' => E::ts('Grant report received'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => E::ts('Yes/No field stating whether grant report was received by donor.'),
      'add' => '1.8',
      'default' => FALSE,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'grant_type_id' => [
      'title' => E::ts('Grant Type'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => E::ts('Type of grant. Implicit FK to civicrm_option_value in grant_type option_group.'),
      'add' => '1.8',
      'usage' => [
        'export',
      ],
      'pseudoconstant' => [
        'option_group_name' => 'grant_type',
      ],
    ],
    'amount_total' => [
      'title' => E::ts('Total Amount'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Requested grant amount, in default currency.'),
      'add' => '1.8',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'amount_requested' => [
      'title' => E::ts('Amount Requested'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => 'Text',
      'description' => E::ts('Requested grant amount, in original currency (optional).'),
      'add' => '1.8',
    ],
    'amount_granted' => [
      'title' => E::ts('Amount granted'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => 'Text',
      'description' => E::ts('Granted amount, in default currency.'),
      'add' => '1.8',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'currency' => [
      'title' => E::ts('Grant Currency'),
      'sql_type' => 'varchar(3)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => E::ts('3 character string, value from config setting or input via user.'),
      'add' => '3.2',
      'input_attrs' => [
        'maxlength' => 3,
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_currency',
        'key_column' => 'name',
        'label_column' => 'full_name',
        'name_column' => 'name',
        'abbr_column' => 'symbol',
      ],
    ],
    'rationale' => [
      'title' => E::ts('Grant Rationale'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('Grant rationale.'),
      'add' => '1.8',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'rows' => 4,
        'cols' => 60,
      ],
    ],
    'status_id' => [
      'title' => E::ts('Grant Status'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => E::ts('ID of Grant status.'),
      'add' => '1.8',
      'unique_name' => 'grant_status_id',
      'usage' => [
        'import',
        'duplicate_matching',
      ],
      'pseudoconstant' => [
        'option_group_name' => 'grant_status',
      ],
    ],
    'financial_type_id' => [
      'title' => E::ts('Financial Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => E::ts('FK to Financial Type.'),
      'add' => '4.3',
      'default' => NULL,
      'input_attrs' => [
        'label' => E::ts('Financial Type'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_financial_type',
        'key_column' => 'id',
        'label_column' => 'name',
      ],
      'entity_reference' => [
        'entity' => 'FinancialType',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
  ],
];
