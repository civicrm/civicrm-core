<?php
return CRM_Core_CodeGen_OptionGroup::create('postal_greeting', 'a/0041')
  ->addMetadata([
    'title' => ts('Postal Greeting Type'),
  ])
  ->addValues([
    [
      'label' => 'Dear {contact.first_name}',
      'value' => 1,
      'name' => 'Dear {contact.first_name}',
      'filter' => 1,
      'is_default' => 1,
    ],
    [
      'label' => 'Dear {contact.prefix_id:label} {contact.first_name} {contact.last_name}',
      'value' => 2,
      'name' => 'Dear {contact.prefix_id:label} {contact.first_name} {contact.last_name}',
      'filter' => 1,
    ],
    [
      'label' => 'Dear {contact.prefix_id:label} {contact.last_name}',
      'value' => 3,
      'name' => 'Dear {contact.prefix_id:label} {contact.last_name}',
      'filter' => 1,
    ],
    [
      'label' => 'Customized',
      'value' => 4,
      'name' => 'Customized',
      'is_reserved' => 1,
    ],
    [
      'label' => 'Dear {contact.household_name}',
      'value' => 5,
      'name' => 'Dear {contact.household_name}',
      'filter' => 2,
      'is_default' => 1,
    ],
  ]);
