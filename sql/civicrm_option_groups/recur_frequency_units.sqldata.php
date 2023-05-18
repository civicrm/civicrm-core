<?php
return CRM_Core_CodeGen_OptionGroup::create('recur_frequency_units', 'a/0032')
  ->addMetadata([
    'title' => ts('Recurring Frequency Units'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('day'), 'day', 'day'],
    [ts('week'), 'week', 'week'],
    [ts('month'), 'month', 'month'],
    [ts('year'), 'year', 'year'],
  ])
  ->addDefaults([
    'is_reserved' => 1,
  ]);
