<?php

return [
  'name' => 'WordReplacement',
  'table' => 'civicrm_word_replacement',
  'class' => 'CRM_Core_DAO_WordReplacement',
  'getInfo' => fn() => [
    'title' => ts('Word Replacement'),
    'title_plural' => ts('Word Replacements'),
    'description' => ts('Top-level hierarchy to support word replacement.'),
    'add' => '4.4',
  ],
  'getIndices' => fn() => [
    'UI_domain_find' => [
      'fields' => [
        'domain_id' => TRUE,
        'find_word' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '4.4',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Word Replacement ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Word replacement ID'),
      'add' => '4.4',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'find_word' => [
      'title' => ts('Replaced Word'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Word which need to be replaced'),
      'add' => '4.4',
      'collate' => 'utf8_bin',
    ],
    'replace_word' => [
      'title' => ts('Replacement Word'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Word which will replace the word in find'),
      'add' => '4.4',
      'collate' => 'utf8_bin',
    ],
    'is_active' => [
      'title' => ts('Word Replacement is Active'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this entry active?'),
      'add' => '4.4',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'match_type' => [
      'title' => ts('Word Replacement Match Type'),
      'sql_type' => 'varchar(16)',
      'input_type' => 'Select',
      'add' => '4.4',
      'default' => 'wildcardMatch',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'getWordReplacementMatchType'],
      ],
    ],
    'domain_id' => [
      'title' => ts('Domain ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Domain ID. This is for Domain specific word replacement'),
      'add' => '1.1',
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
  ],
];
