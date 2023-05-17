<?php
return CRM_Core_CodeGen_OptionGroup::create('website_type', 'a/0045')
  ->addMetadata([
    'title' => ts('Website Type'),
  ])
  ->addValues(['label', 'name', 'value', 'weight'], [
    ['Work', 'Work', 1, 1, 'is_default' => 1],
    ['Main', 'Main', 2, 2],
    ['Facebook', 'Facebook', 3, 3],
    ['Instagram', 'Instagram', 5, 5],
    ['LinkedIn', 'LinkedIn', 6, 6],
    ['MySpace', 'MySpace', 7, 7],
    ['Pinterest', 'Pinterest', 8, 8],
    ['SnapChat', 'SnapChat', 9, 9],
    ['Tumblr', 'Tumblr', 10, 10],
    ['Twitter', 'Twitter', 11, 11],
    ['Vine', 'Vine ', 12, 12],
  ]);
