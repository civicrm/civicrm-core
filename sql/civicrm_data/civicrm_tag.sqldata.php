<?php
return CRM_Core_CodeGen_SqlData::create('civicrm_tag')
  ->addDefaults([
    'parent_id' => NULL,
    'used_for' => 'civicrm_contact',
  ])
  ->addValues([
    [
      'name' => ts('Non-profit'),
      'description' => ts('Any not-for-profit organization.'),
    ],
    [
      'name' => ts('Company'),
      'description' => ts('For-profit organization.'),
    ],
    [
      'name' => ts('Government Entity'),
      'description' => ts('Any governmental entity.'),
    ],
    [
      'name' => ts('Major Donor'),
      'description' => ts('High-value supporter of our organization.'),
    ],
    [
      'name' => ts('Volunteer'),
      'description' => ts('Active volunteers.'),
    ],
  ]);
