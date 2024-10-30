<?php
return CRM_Core_CodeGen_OptionGroup::create('soft_credit_type', 'a/0096')
  ->addMetadata([
    'title' => ts('Soft Credit Types'),
  ])
  ->addValues([
    [
      'label' => ts('In Honor of'),
      'value' => 1,
      'name' => 'in_honor_of',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('In Memory of'),
      'value' => 2,
      'name' => 'in_memory_of',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Solicited'),
      'value' => 3,
      'name' => 'solicited',
      'is_default' => 1,
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Household'),
      'value' => 4,
      'name' => 'household',
    ],
    [
      'label' => ts('Workplace Giving'),
      'value' => 5,
      'name' => 'workplace',
    ],
    [
      'label' => ts('Foundation Affiliate'),
      'value' => 6,
      'name' => 'foundation_affiliate',
    ],
    [
      'label' => ts('3rd-party Service'),
      'value' => 7,
      'name' => '3rd-party_service',
    ],
    [
      'label' => ts('Donor-advised Fund'),
      'value' => 8,
      'name' => 'donor-advised_fund',
    ],
    [
      'label' => ts('Matched Gift'),
      'value' => 9,
      'name' => 'matched_gift',
    ],
    [
      'label' => ts('Personal Campaign Page'),
      'value' => 10,
      'name' => 'pcp',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Gift'),
      'value' => 11,
      'name' => 'gift',
      'is_reserved' => 1,
    ],
  ]);
