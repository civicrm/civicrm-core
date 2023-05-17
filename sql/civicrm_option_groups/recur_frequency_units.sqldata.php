<?php
return CRM_Core_CodeGen_OptionGroup::create('recur_frequency_units', 'a/0032')
  ->addMetadata([
    'title' => ts('Recurring Frequency Units'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('day'), 'day', 'day', 'is_reserved' => 1],
    [ts('week'), 'week', 'week', 'is_reserved' => 1],
    [ts('month'), 'month', 'month', 'is_reserved' => 1],
    [ts('year'), 'year', 'year', 'is_reserved' => 1],
  ]);
