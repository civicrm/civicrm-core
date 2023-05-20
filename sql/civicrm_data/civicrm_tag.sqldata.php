<?php
return CRM_Core_CodeGen_SqlData::create('civicrm_tag')
  ->addValues([
    [
      'name' => ts('Non-profit'),
      'description' => ts('Any not-for-profit organization.'),
      'parent_id' => NULL,
      'used_for' => 'civicrm_contact',
    ],
    [
      'name' => ts('Company'),
      'description' => ts('For-profit organization.'),
      'parent_id' => NULL,
      'used_for' => 'civicrm_contact',
    ],
    [
      'name' => ts('Government Entity'),
      'description' => ts('Any governmental entity.'),
      'parent_id' => NULL,
      'used_for' => 'civicrm_contact',
    ],
    [
      'name' => ts('Major Donor'),
      'description' => ts('High-value supporter of our organization.'),
      'parent_id' => NULL,
      'used_for' => 'civicrm_contact',
    ],
    [
      'name' => ts('Volunteer'),
      'description' => ts('Active volunteers.'),
      'parent_id' => NULL,
      'used_for' => 'civicrm_contact',
    ],
  ]);
