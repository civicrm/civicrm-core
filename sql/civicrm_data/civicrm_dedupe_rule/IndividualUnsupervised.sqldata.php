<?php
// IndividualUnsupervised uses hard-coded optimized query (CRM_Dedupe_BAO_QueryBuilder_IndividualUnsupervised)
return CRM_Core_CodeGen_DedupeRule::create('IndividualUnsupervised')
  ->addMetadata([
    'contact_type' => 'Individual',
    'threshold' => 10,
    'used' => 'Unsupervised',
    'title' => ts('Email (reserved)'),
    'is_reserved' => 1,
  ])
  ->addValueTable(['rule_table', 'rule_field', 'rule_weight'], [
    ['civicrm_email', 'email', 10],
  ]);
