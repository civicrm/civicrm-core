<?php
return CRM_Core_CodeGen_DedupeRule::create('HouseholdSupervised')
  ->addMetadata([
    'contact_type' => 'Household',
    'threshold' => 10,
    'used' => 'Supervised',
    'title' => ts('Household Name or Email'),
    'is_reserved' => 0,
  ])
  ->addValueTable(['rule_table', 'rule_field', 'rule_weight'], [
    ['civicrm_contact', 'household_name', 10],
    ['civicrm_email', 'email', 10],
  ]);
