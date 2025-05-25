<?php

return [
  'name' => 'TranslationSource',
  'table' => 'civicrm_translation_source',
  'class' => 'CRM_Core_DAO_TranslationSource',
  'getInfo' => fn() => [
    'title' => ts('Translated Source String'),
    'title_plural' => ts('Translated Source Strings'),
    'description' => ts('A source reference for strings that should be translated.'),
    'log' => TRUE,
    'add' => '6.4.alpha1',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Source ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique Source ID'),
      'add' => '6.4.alpha1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'source' => [
      'title' => ts('Source Text'),
      'sql_type' => 'longtext',
      'input_type' => 'TextArea',
      'required' => TRUE,
      'description' => ts('Source text for referencing translations'),
      'add' => '6.4.alpha1',
    ],
  ],
];
