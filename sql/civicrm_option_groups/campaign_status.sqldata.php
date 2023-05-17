<?php
return CRM_Core_CodeGen_OptionGroup::create('campaign_status', 'a/0052')
  ->addMetadata([
    'title' => ts('Campaign Status'),
  ])
  ->addValues(['label', 'name', 'value', 'weight'], [
    [ts('Planned'), 'Planned', 1, 1],
    [ts('In Progress'), 'In Progress', 2, 1],
    [ts('Completed'), 'Completed', 3, 1],
    [ts('Cancelled'), 'Cancelled', 4, 1],
  ]);
