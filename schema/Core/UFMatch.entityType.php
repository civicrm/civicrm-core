<?php

return [
  'name' => 'UFMatch',
  'table' => 'civicrm_uf_match',
  'class' => 'CRM_Core_DAO_UFMatch',
  'getInfo' => fn() => [
    'title' => ts('User Account'),
    'title_plural' => ts('User Accounts'),
    'description' => ts('The mapping from an user framework (UF) object to a CRM object.'),
    'log' => TRUE,
    'add' => '1.1',
  ],
  'getIndices' => fn() => [
    'I_civicrm_uf_match_uf_id' => [
      'fields' => [
        'uf_id' => TRUE,
      ],
      'add' => '3.3',
    ],
    'UI_uf_match_uf_id_domain_id' => [
      'fields' => [
        'uf_id' => TRUE,
        'domain_id' => TRUE,
      ],
      'add' => '5.69',
    ],
    'UI_uf_name_domain_id' => [
      'fields' => [
        'uf_name' => TRUE,
        'domain_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '2.1',
    ],
    'UI_contact_domain_id' => [
      'fields' => [
        'contact_id' => TRUE,
        'domain_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '1.6',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('UF Match ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('System generated ID.'),
      'add' => '1.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'domain_id' => [
      'title' => ts('Domain ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Which Domain is this match entry for'),
      'add' => '3.0',
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
      ],
    ],
    'uf_id' => [
      'title' => ts('CMS ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('UF ID'),
      'add' => '1.1',
    ],
    'uf_name' => [
      'title' => ts('CMS Unique Identifier'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('UF Name'),
      'add' => '1.9',
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Contact ID'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'language' => [
      'title' => ts('Preferred Language'),
      'sql_type' => 'varchar(5)',
      'input_type' => 'Text',
      'description' => ts('UI language preferred by the given user/contact'),
      'add' => '2.1',
    ],
  ],
];
