<?php
return CRM_Core_CodeGen_OptionGroup::create('addressee', 'a/0042')
  ->addMetadata([
    'title' => ts('Addressee Type'),
  ])
  ->addValues([
    [
      'label' => '{contact.prefix_id:label}{ }{contact.first_name}{ }{contact.middle_name}{ }{contact.last_name}{ }{contact.suffix_id:label}',
      'value' => 1,
      'name' => '{contact.prefix_id:label}{ }{contact.first_name}{ }{contact.middle_name}{ }{contact.last_name}{ }{contact.suffix_id:label}',
      'filter' => 1,
      'is_default' => 1,
    ],
    [
      'label' => '{contact.household_name}',
      'value' => 2,
      'name' => '{contact.household_name}',
      'filter' => 2,
      'is_default' => 1,
    ],
    [
      'label' => '{contact.organization_name}',
      'value' => 3,
      'name' => '{contact.organization_name}',
      'filter' => 3,
      'is_default' => 1,
    ],
    [
      'label' => 'Customized',
      'value' => 4,
      'name' => 'Customized',
      'is_reserved' => 1,
    ],
  ]);
