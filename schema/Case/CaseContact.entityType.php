<?php

return [
  'name' => 'CaseContact',
  'table' => 'civicrm_case_contact',
  'class' => 'CRM_Case_DAO_CaseContact',
  'getInfo' => fn() => [
    'title' => ts('Case Contact'),
    'title_plural' => ts('Case Contacts'),
    'description' => ts('Joining table for case-contact associations.'),
    'log' => TRUE,
    'add' => '2.1',
  ],
  'getIndices' => fn() => [
    'UI_case_contact_id' => [
      'fields' => [
        'case_id' => TRUE,
        'contact_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '2.1',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Case Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique case-contact association id'),
      'add' => '2.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'case_id' => [
      'title' => ts('Case ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Case ID of case-contact association.'),
      'add' => '2.1',
      'input_attrs' => [
        'label' => ts('Case'),
      ],
      'entity_reference' => [
        'entity' => 'Case',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Contact ID of contact record given case belongs to.'),
      'add' => '2.1',
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
