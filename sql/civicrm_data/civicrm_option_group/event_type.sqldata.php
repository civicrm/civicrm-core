<?php
return CRM_Core_CodeGen_OptionGroup::create('event_type', 'a/0015')
  ->addMetadata([
    'title' => ts('Event Type'),
    'description' => ts('Use Event Types to categorize your events. Event feeds can be filtered by Event Type and participant searches can use Event Type as a criteria.'),
    'data_type' => 'Integer',
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Conference'), 'Conference', 1],
    [ts('Exhibition'), 'Exhibition', 2],
    [ts('Fundraiser'), 'Fundraiser', 3],
    [ts('Meeting'), 'Meeting', 4],
    [ts('Performance'), 'Performance', 5],
    [ts('Workshop'), 'Workshop', 6],
  ]);
