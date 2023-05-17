<?php

// CRM-13833
return CRM_Core_CodeGen_OptionGroup::create('soft_credit_type', 'b/98')
  ->addMetadata([
    'title' => ts('Soft Credit Types'),
  ])
  ->addValues(['label', 'value', 'name'], [
    [ts('In Honor of'), 1, 'in_honor_of', 'is_reserved' => 1],
    [ts('In Memory of'), 2, 'in_memory_of', 'is_reserved' => 1],
    [ts('Solicited'), 3, 'solicited', 'is_reserved' => 1, 'is_default' => 1],
    [ts('Household'), 4, 'household'],
    [ts('Workplace Giving'), 5, 'workplace'],
    [ts('Foundation Affiliate'), 6, 'foundation_affiliate'],
    [ts('3rd-party Service'), 7, '3rd-party_service'],
    [ts('Donor-advised Fund'), 8, 'donor-advised_fund'],
    [ts('Matched Gift'), 9, 'matched_gift'],
    [ts('Personal Campaign Page'), 10, 'pcp', 'is_reserved' => 1],
    [ts('Gift'), 11, 'gift', 'is_reserved' => 1],
  ])
  ->addDefaults([]);
