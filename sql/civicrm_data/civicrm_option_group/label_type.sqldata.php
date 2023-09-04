<?php
return CRM_Core_CodeGen_OptionGroup::create('label_type', 'a/0072')
  ->addMetadata([
    'title' => ts('Label Type'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Event Badge'), 'Event Badge', 1],
  ])
  ->addDefaults([
    'is_default' => NULL,
  ]);
