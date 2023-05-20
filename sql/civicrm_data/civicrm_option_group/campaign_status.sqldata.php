<?php
return CRM_Core_CodeGen_OptionGroup::create('campaign_status', 'a/0052')
  ->addMetadata([
    'title' => ts('Campaign Status'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Planned'), 'Planned', 1],
    [ts('In Progress'), 'In Progress', 2],
    [ts('Completed'), 'Completed', 3],
    [ts('Cancelled'), 'Cancelled', 4],
  ])
  ->addDefaults([
    'weight' => 1,
  ]);
