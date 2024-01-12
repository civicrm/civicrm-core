<?php
return [
  'acl_financial_type' => [
    'group_name' => 'Contribute Preferences',
    'group' => 'contribute',
    'name' => 'acl_financial_type',
    'type' => 'Boolean',
    'html_type' => 'checkbox',
    'quick_form_type' => 'Element',
    'default' => 0,
    'add' => '4.7',
    'title' => ts('Enable Access Control by Financial Type'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => NULL,
    'help' => ['id' => 'acl_financial_type', 'file' => 'CRM/Admin/Form/Preferences/Contribute.hlp'],
    'settings_pages' => ['contribute' => ['weight' => 30]],
    'on_change' => [
      'financialacls_toggle',
    ],
  ],
];
