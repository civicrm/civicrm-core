<?php
return CRM_Core_CodeGen_OptionGroup::create('email_greeting', 'a/0040')
  ->addMetadata([
    'title' => ts('Email Greeting Type'),
  ])
  ->addValues([
    [
      'value' => 1,
      'name' => 'Dear {contact.first_name}',
      'filter' => 1,
      'is_default' => 1,
    ],
    [
      'value' => 2,
      'name' => 'Dear {contact.prefix_id:label} {contact.first_name} {contact.last_name}',
      'filter' => 1,
    ],
    [
      'value' => 3,
      'name' => 'Dear {contact.prefix_id:label} {contact.last_name}',
      'filter' => 1,
    ],
    [
      'value' => 4,
      'name' => 'Customized',
      'is_reserved' => 1,
    ],
    [
      'value' => 5,
      'name' => 'Dear {contact.household_name}',
      'filter' => 2,
      'is_default' => 1,
    ],
  ])
  ->syncColumns('fill', ['name' => 'label']);
