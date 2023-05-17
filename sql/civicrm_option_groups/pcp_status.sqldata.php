<?php
return CRM_Core_CodeGen_OptionGroup::create('pcp_status', 'a/0012')
  ->addMetadata([
    'title' => ts('PCP Status'),
    'is_locked' => 1,
  ])
  ->addValues(['label', 'name', 'value'], [
    [ts('Waiting Review'), 'Waiting Review', 1, 'is_reserved' => 1],
    [ts('Approved'), 'Approved', 2, 'is_reserved' => 1],
    [ts('Not Approved'), 'Not Approved', 3, 'is_reserved' => 1],
  ]);
