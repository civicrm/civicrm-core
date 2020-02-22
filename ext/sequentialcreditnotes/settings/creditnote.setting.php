<?php
return [
  'credit_notes_prefix' => [
    'group_name' => 'Contribute Preferences',
    'group' => 'contribute',
    'name' => 'credit_notes_prefix',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'add' => '5.23',
    'type' => CRM_Utils_Type::T_STRING,
    'title' => ts('Credit Notes Prefix'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Prefix to be prepended to credit note ids'),
    'default' => 'CN_',
    'help_text' => ts('The credit note ID is generated when a contribution is set to Refunded, Cancelled or Chargeback. It is visible on invoices, if invoices are enabled'),
    'settings_pages' => ['contribute' => ['weight' => 80]],
  ],
];
