<?php
return CRM_Core_CodeGen_OptionGroup::create('wysiwyg_presets', 'a/0077')
  ->addMetadata([
    'title' => ts('WYSIWYG Editor Presets'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Default'), 'default', 1, 'is_default' => 1],
    [ts('CiviMail'), 'civimail', 2, 'component_id' => 4],
    [ts('CiviEvent'), 'civievent', 3, 'component_id' => 1],
  ])
  ->addDefaults([
    'filter' => NULL,
    'is_reserved' => 1,
  ]);
