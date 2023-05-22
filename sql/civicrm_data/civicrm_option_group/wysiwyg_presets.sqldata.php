<?php
return CRM_Core_CodeGen_OptionGroup::create('wysiwyg_presets', 'a/0077')
  ->addMetadata([
    'title' => ts('WYSIWYG Editor Presets'),
  ])
  ->addValues([
    [
      'label' => ts('Default'),
      'value' => 1,
      'name' => 'default',
      'is_default' => 1,
    ],
    [
      'label' => ts('CiviMail'),
      'value' => 2,
      'name' => 'civimail',
      'component_id' => 4,
    ],
    [
      'label' => ts('CiviEvent'),
      'value' => 3,
      'name' => 'civievent',
      'component_id' => 1,
    ],
  ])
  ->addDefaults([
    'filter' => NULL,
    'is_reserved' => 1,
  ]);
