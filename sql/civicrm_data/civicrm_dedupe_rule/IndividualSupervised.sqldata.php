<?php
// IndividualSupervised uses hard-coded optimized query (CRM_Dedupe_BAO_QueryBuilder_IndividualSupervised)
return CRM_Core_CodeGen_DedupeRule::create('IndividualSupervised')
  ->addMetadata([
    'contact_type' => 'Individual',
    'threshold' => 20,
    'used' => 'Supervised',
    'title' => ts('First Name, Last Name and Email (reserved)'),
    'is_reserved' => 1,
  ])
  ->addValueTable(['rule_table', 'rule_field', 'rule_weight'], [
    ['civicrm_contact', 'first_name', 5],
    ['civicrm_contact', 'last_name', 7],
    ['civicrm_email', 'email', 10],
  ]);
