<?php
return CRM_Core_CodeGen_OptionGroup::create('recur_frequency_units', 'a/0032')
  ->addMetadata([
    'title' => ts('Recurring Frequency Units'),
  ])
  ->addValueTable(['label', 'name'], [
    [ts('day'), 'day'],
    [ts('week'), 'week'],
    [ts('month'), 'month'],
    [ts('year'), 'year'],
  ])
  ->syncColumns('fill', ['name' => 'value'])
  ->addDefaults([
    'is_reserved' => 1,
  ]);
