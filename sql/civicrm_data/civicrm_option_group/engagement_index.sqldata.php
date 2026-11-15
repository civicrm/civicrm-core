<?php
return CRM_Core_CodeGen_OptionGroup::create('engagement_index', 'a/0055')
  ->addMetadata([
    'title' => ts('Engagement Index'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    ['1', 1, 1],
    ['2', 2, 2],
    ['3', 3, 3],
    ['4', 4, 4],
    ['5', 5, 5],
  ]);
