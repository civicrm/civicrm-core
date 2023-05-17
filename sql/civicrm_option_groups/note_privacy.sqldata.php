<?php
return CRM_Core_CodeGen_OptionGroup::create('note_privacy', 'a/0050')
  ->addMetadata([
    'title' => ts('Privacy levels for notes'),
  ])
  ->addValues(['label', 'name', 'value'], [
    [ts('None'), 'None', 0, 'is_default' => 1, 'is_reserved' => 1],
    [ts('Author Only'), 'Author Only', 1, 'is_reserved' => 1],
  ]);
