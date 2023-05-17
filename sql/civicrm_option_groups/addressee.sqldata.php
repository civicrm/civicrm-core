<?php
return CRM_Core_CodeGen_OptionGroup::create('addressee', 'a/0042')
  ->addMetadata([
    'title' => ts('Addressee Type'),
  ])
  ->addValues(['label', 'name', 'value'], [
    ['{contact.prefix_id:label}{ }{contact.first_name}{ }{contact.middle_name}{ }{contact.last_name}{ }{contact.suffix_id:label}', '{contact.prefix_id:label}{ }{contact.first_name}{ }{contact.middle_name}{ }{contact.last_name}{ }{contact.suffix_id:label}', 1, 'filter' => 1, 'is_default' => 1],
    ['{contact.household_name}', '{contact.household_name}', 2, 'filter' => 2, 'is_default' => 1],
    ['{contact.organization_name}', '{contact.organization_name}', 3, 'filter' => 3, 'is_default' => 1],
    ['Customized', 'Customized', 4, 'is_reserved' => 1],
  ]);
