<?php
return CRM_Core_CodeGen_OptionGroup::create('group_type', 'a/0022')
  ->addMetadata([
    'title' => ts('Group Type'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Access Control'), 'Access Control', 1],
    [ts('Mailing List'), 'Mailing List', 2],
  ])
  ->addDefaults([
    'is_reserved' => 1,
  ]);
