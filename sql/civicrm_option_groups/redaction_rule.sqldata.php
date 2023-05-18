<?php
return CRM_Core_CodeGen_OptionGroup::create('redaction_rule', 'a/0038')
  ->addMetadata([
    'title' => ts('Redaction Rule'),
  ])
  ->addValues([
    [
      'label' => 'Vancouver',
      'value' => 'city_',
      'name' => 'city_',
      'is_active' => 0,
    ],
    [
      'label' => '/(19|20)(\\d{2})-(\\d{1,2})-(\\d{1,2})/',
      'value' => 'date_',
      'name' => 'date_',
      'filter' => 1,
      'is_active' => 0,
    ],
  ]);
