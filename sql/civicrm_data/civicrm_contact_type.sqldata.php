<?php
return CRM_Core_CodeGen_SqlData::create('civicrm_contact_type')
  ->addDefaults([
    'image_URL' => NULL,
    'parent_id' => NULL,
    'is_active' => 1,
    'is_reserved' => 1,
  ])
  ->addValues([
    [
      'name' => 'Individual',
      'label' => ts('Individual'),
      'icon' => 'fa-user',
    ],
    [
      'name' => 'Household',
      'label' => ts('Household'),
      'icon' => 'fa-home',
    ],
    [
      'name' => 'Organization',
      'label' => ts('Organization'),
      'icon' => 'fa-building',
    ],
  ]);
