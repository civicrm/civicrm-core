<?php
return CRM_Core_CodeGen_OptionGroup::create('environment', 'a/0081')
  ->addMetadata([
    'title' => ts('Environment'),
  ])
  ->addValues([
    [
      'label' => ts('Production'),
      'value' => 'Production',
      'name' => 'Production',
      'is_default' => 1,
      'description' => ts('Production Environment'),
    ],
    [
      'label' => ts('Staging'),
      'value' => 'Staging',
      'name' => 'Staging',
      'description' => ts('Staging Environment'),
    ],
    [
      'label' => ts('Development'),
      'value' => 'Development',
      'name' => 'Development',
      'description' => ts('Development Environment'),
    ],
  ])
  ->addDefaults([
    'filter' => NULL,
    'is_reserved' => 1,
  ]);
