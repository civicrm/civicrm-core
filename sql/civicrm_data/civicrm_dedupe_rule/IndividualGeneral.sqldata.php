<?php
// IndividualGeneral uses hard-coded optimized query (CRM_Dedupe_BAO_QueryBuilder_IndividualGeneral)
return CRM_Core_CodeGen_DedupeRule::create('IndividualGeneral')
  ->addMetadata([
    'contact_type' => 'Individual',
    'threshold' => 15,
    'used' => 'General',
    'title' => ts('First Name, Last Name and Street Address (reserved)'),
    'is_reserved' => 1,
  ])
  ->addValueTable(['rule_table', 'rule_field', 'rule_weight'], [
    ['civicrm_contact', 'first_name', '5'],
    ['civicrm_contact', 'last_name', '5'],
    ['civicrm_address', 'street_address', '5'],
    ['civicrm_contact', 'middle_name', '1'],
    ['civicrm_contact', 'suffix_id', '1'],
  ]);
