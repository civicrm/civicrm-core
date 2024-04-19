<?php

return [
  'name' => 'DashboardContact',
  'table' => 'civicrm_dashboard_contact',
  'class' => 'CRM_Contact_DAO_DashboardContact',
  'getInfo' => fn() => [
    'title' => ts('Dashboard Contact'),
    'title_plural' => ts('Dashboard Contacts'),
    'description' => ts('Table to store dashboard for each contact.'),
    'add' => '3.1',
  ],
  'getIndices' => fn() => [
    'index_dashboard_id_contact_id' => [
      'fields' => [
        'dashboard_id' => TRUE,
        'contact_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '4.7',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Dashboard Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '3.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'dashboard_id' => [
      'title' => ts('Dashboard ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Dashboard ID'),
      'add' => '3.1',
      'input_attrs' => [
        'label' => ts('Dashboard'),
      ],
      'entity_reference' => [
        'entity' => 'Dashboard',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Contact ID'),
      'add' => '3.1',
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'column_no' => [
      'title' => ts('Column No'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'description' => ts('column no for this widget'),
      'add' => '3.1',
      'default' => 0,
      'input_attrs' => [
        'label' => ts('Column Number'),
      ],
    ],
    'is_active' => [
      'title' => ts('Dashlet is Active?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this widget active?'),
      'add' => '3.1',
      'default' => FALSE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'weight' => [
      'title' => ts('Order'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'description' => ts('Ordering of the widgets.'),
      'add' => '3.1',
      'default' => 0,
    ],
  ],
];
