<?php
return CRM_Core_CodeGen_OptionGroup::create('mobile_provider', 'a/0005')
  ->addMetadata([
    'title' => ts('Mobile Phone Providers'),
    'description' => ts('When recording mobile phone numbers for contacts, it may be useful to include the Mobile Phone Service Provider (e.g. Cingular, Sprint, etc.). CiviCRM is installed with the most commonly encountered service providers. Administrators may define as many additional providers as needed.'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    ['Sprint', 'Sprint', 1],
    ['Verizon', 'Verizon', 2],
    ['Cingular', 'Cingular', 3],
  ]);
