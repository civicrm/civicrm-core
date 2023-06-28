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
      'id' => 1,
      'name' => 'Individual',
      'label' => ts('Individual'),
      'icon' => 'fa-user',
    ],
    [
      'id' => 2,
      'name' => 'Household',
      'label' => ts('Household'),
      'icon' => 'fa-home',
    ],
    [
      'id' => 3,
      'name' => 'Organization',
      'label' => ts('Organization'),
      'icon' => 'fa-building',
    ],
  ]);
