<?php
return CRM_Core_CodeGen_DedupeRule::create('OrganizationSupervised')
  ->addMetadata([
    'contact_type' => 'Organization',
    'threshold' => 10,
    'used' => 'Supervised',
    'title' => ts('Organization Name or Email'),
    'is_reserved' => 0,
  ])
  ->addValueTable(['rule_table', 'rule_field', 'rule_weight'], [
    ['civicrm_contact', 'organization_name', 10],
    ['civicrm_email', 'email', 10],
  ]);
