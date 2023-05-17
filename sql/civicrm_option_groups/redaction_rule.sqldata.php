<?php
return CRM_Core_CodeGen_OptionGroup::create('redaction_rule', 'a/0038')
  ->addMetadata([
    'title' => ts('Redaction Rule'),
  ])
  ->addValues(['label', 'name', 'value'], [
    ['Vancouver', 'city_', 'city_', 'is_active' => 0],
    ['/(19|20)(\\d{2})-(\\d{1,2})-(\\d{1,2})/', 'date_', 'date_', 'filter' => 1, 'is_active' => 0],
  ]);
