<?php
return CRM_Core_CodeGen_OptionGroup::create('pcp_status', 'a/0012')
  ->addMetadata([
    'title' => ts('PCP Status'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Waiting Review'), 'Waiting Review', 1],
    [ts('Approved'), 'Approved', 2],
    [ts('Not Approved'), 'Not Approved', 3],
  ])
  ->addDefaults([
    'is_reserved' => 1,
  ]);
