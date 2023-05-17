<?php
return CRM_Core_CodeGen_OptionGroup::create('communication_style', 'a/0074')
  ->addMetadata([
    'title' => ts('Communication Style'),
  ])
  ->addValues(['label', 'name', 'value'], [
    [ts('Formal'), 'formal', 1, 'is_default' => 1],
    [ts('Familiar'), 'familiar', 2],
  ]);
