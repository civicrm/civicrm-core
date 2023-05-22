<?php
return CRM_Core_CodeGen_SqlData::create('civicrm_contact_type')
  ->addValues([
    [
      'id' => 1,
      'name' => 'Individual',
      'label' => ts('Individual'),
      'image_URL' => NULL,
      'parent_id' => NULL,
      'is_active' => 1,
      'is_reserved' => 1,
      'icon' => 'fa-user',
    ],
    [
      'id' => 2,
      'name' => 'Household',
      'label' => ts('Household'),
      'image_URL' => NULL,
      'parent_id' => NULL,
      'is_active' => 1,
      'is_reserved' => 1,
      'icon' => 'fa-home',
    ],
    [
      'id' => 3,
      'name' => 'Organization',
      'label' => ts('Organization'),
      'image_URL' => NULL,
      'parent_id' => NULL,
      'is_active' => 1,
      'is_reserved' => 1,
      'icon' => 'fa-building',
    ],
  ]);
