<?php
return CRM_Core_CodeGen_OptionGroup::create('email_greeting', 'a/0040')
  ->addMetadata([
    'title' => ts('Email Greeting Type'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    ['Dear {contact.first_name}', 'Dear {contact.first_name}', 1, 'filter' => 1, 'is_default' => 1],
    ['Dear {contact.prefix_id:label} {contact.first_name} {contact.last_name}', 'Dear {contact.prefix_id:label} {contact.first_name} {contact.last_name}', 2, 'filter' => 1],
    ['Dear {contact.prefix_id:label} {contact.last_name}', 'Dear {contact.prefix_id:label} {contact.last_name}', 3, 'filter' => 1],
    ['Customized', 'Customized', 4, 'is_reserved' => 1],
    ['Dear {contact.household_name}', 'Dear {contact.household_name}', 5, 'filter' => 2, 'is_default' => 1],
  ]);
