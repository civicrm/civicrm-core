<?php
return CRM_Core_CodeGen_OptionGroup::create('website_type', 'a/0045')
  ->addMetadata([
    'title' => ts('Website Type'),
  ])
  ->addValueTable(['name', 'value'], [
    ['Work', 1, 'is_default' => 1],
    ['Main', 2],
    ['Facebook', 3],
    ['Instagram', 5],
    ['LinkedIn', 6],
    ['MySpace', 7],
    ['Pinterest', 8],
    ['SnapChat', 9],
    ['Tumblr', 10],
    ['Twitter', 11],
    ['Vine ', 12, 'label' => 'Vine'],
    // This appears to be locking-in a typo on the 'name' side, but they fixed the 'label'.
  ])
  ->syncColumns('fill', ['value' => 'weight', 'name' => 'label']);
