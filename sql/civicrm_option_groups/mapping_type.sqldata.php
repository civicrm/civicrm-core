<?php
return CRM_Core_CodeGen_OptionGroup::create('mapping_type', 'a/0030')
  ->addMetadata([
    'title' => ts('Mapping Type'),
    'is_locked' => 1,
  ])
  ->addValues(['label', 'name', 'value', 'weight'], [
    [ts('Search Builder'), 'Search Builder', 1, 1, 'is_reserved' => 1],
    [ts('Import Contact'), 'Import Contact', 2, 2, 'is_reserved' => 1],
    [ts('Import Activity'), 'Import Activity', 3, 3, 'is_reserved' => 1],
    [ts('Import Contribution'), 'Import Contribution', 4, 4, 'is_reserved' => 1],
    [ts('Import Membership'), 'Import Membership', 5, 5, 'is_reserved' => 1],
    [ts('Import Participant'), 'Import Participant', 6, 6, 'is_reserved' => 1],
    [ts('Export Contact'), 'Export Contact', 7, 7, 'is_reserved' => 1],
    [ts('Export Contribution'), 'Export Contribution', 8, 8, 'is_reserved' => 1],
    [ts('Export Membership'), 'Export Membership', 9, 9, 'is_reserved' => 1],
    [ts('Export Participant'), 'Export Participant', 10, 10, 'is_reserved' => 1],
    [ts('Export Pledge'), 'Export Pledge', 11, 11, 'is_reserved' => 1],
    [ts('Export Case'), 'Export Case', 12, 12, 'is_reserved' => 1],
    [ts('Export Activity'), 'Export Activity', 14, 14, 'is_reserved' => 1],
  ]);
