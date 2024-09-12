<?php
return CRM_Core_CodeGen_OptionGroup::create('website_type', 'a/0045')
  ->addMetadata([
    'title' => ts('Website Type'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Work'), 'Work', 1, 'is_default' => 1],
    [ts('Main'), 'Main', 2],
    [ts('Social'), 'Social', 3],
  ])
  ->syncColumns('fill', ['value' => 'weight', 'name' => 'label']);
