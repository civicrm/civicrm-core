<?php
return CRM_Core_CodeGen_OptionGroup::create('engagement_index', 'a/0055')
  ->addMetadata([
    'title' => ts('Engagement Index'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('1'), 1, 1],
    [ts('2'), 2, 2],
    [ts('3'), 3, 3],
    [ts('4'), 4, 4],
    [ts('5'), 5, 5],
  ]);
