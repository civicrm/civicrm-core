<?php
return CRM_Core_CodeGen_OptionGroup::create('website_type', 'a/0045')
  ->addMetadata([
    'title' => ts('Website Type'),
  ])
  ->addValueTable(['name', 'value'], [
    ['Work', 1, 'is_default' => 1],
    ['Main', 2],
    ['Social', 3],
  ])
  ->syncColumns('fill', ['value' => 'weight', 'name' => 'label']);
