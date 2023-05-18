<?php
return CRM_Core_CodeGen_OptionGroup::create('addressee', 'a/0042')
  ->addMetadata([
    'title' => ts('Addressee Type'),
  ])
  ->addValues([
    [
      'value' => 1,
      'name' => '{contact.prefix_id:label}{ }{contact.first_name}{ }{contact.middle_name}{ }{contact.last_name}{ }{contact.suffix_id:label}',
      'filter' => 1,
      'is_default' => 1,
    ],
    [
      'value' => 2,
      'name' => '{contact.household_name}',
      'filter' => 2,
      'is_default' => 1,
    ],
    [
      'value' => 3,
      'name' => '{contact.organization_name}',
      'filter' => 3,
      'is_default' => 1,
    ],
    [
      'value' => 4,
      'name' => 'Customized',
      'is_reserved' => 1,
    ],
  ])
  ->syncColumns('fill', ['name' => 'label']);
