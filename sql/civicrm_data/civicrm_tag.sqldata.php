<?php
return CRM_Core_CodeGen_SqlData::create('civicrm_tag')
  ->addDefaults([
    'parent_id' => NULL,
    'used_for' => 'civicrm_contact',
  ])
  ->addValues([
    [
      'label' => ts('Non-profit'),
      'name' => 'Non_profit',
      'description' => ts('Any not-for-profit organization.'),
      'color' => '#0bcb21',
    ],
    [
      'label' => ts('Company'),
      'name' => 'Company',
      'description' => ts('For-profit organization.'),
      'color' => '#2260c3',
    ],
    [
      'label' => ts('Government Entity'),
      'name' => 'Government_Entity',
      'description' => ts('Any governmental entity.'),
      'color' => '#cd4b13',
    ],
    [
      'label' => ts('Major Donor'),
      'name' => 'Major_Donor',
      'description' => ts('High-value supporter of our organization.'),
      'color' => '#0cdae9',
    ],
    [
      'label' => ts('Volunteer'),
      'name' => 'Volunteer',
      'description' => ts('Active volunteers.'),
      'color' => '#f0dc00',
    ],
  ]);
