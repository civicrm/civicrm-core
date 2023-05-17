<?php
return CRM_Core_CodeGen_OptionGroup::create('environment', 'a/0081')
  ->addMetadata([
    'title' => ts('Environment'),
  ])
  ->addValues(['label', 'name', 'value', 'description'], [
    [ts('Production'), 'Production', 'Production', ts('Production Environment'), 'is_default' => 1],
    [ts('Staging'), 'Staging', 'Staging', ts('Staging Environment')],
    [ts('Development'), 'Development', 'Development', ts('Development Environment')],
  ])
  ->addDefaults([
    'filter' => NULL,
    'is_reserved' => 1,
  ]);
