<?php
return [
  'enable_innodb_fts' => [
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'enable_innodb_fts',
    'type' => 'Boolean',
    'html_type' => 'toggle',
    'default' => 0,
    'add' => '4.4',
    'title' => ts('InnoDB Full Text Search'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => ts('Enable InnoDB full-text search optimizations. (Requires MySQL 5.6+)'),
    'on_change' => [
      ['CRM_Core_InnoDBIndexer', 'onToggleFts'],
    ],
    'settings_pages' => ['search' => ['section' => 'legacy', 'weight' => 100]],
  ],
  'fts_query_mode' => [
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'fts_query_mode',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_attributes' => [
      'size' => 64,
      'maxlength' => 64,
    ],
    'html_type' => 'text',
    'default' => 'simple',
    'add' => '4.5',
    'title' => ts('How to handle full-text queries'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => NULL,
  ],
];
