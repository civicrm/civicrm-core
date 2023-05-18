<?php
return CRM_Core_CodeGen_OptionGroup::create('mapping_type', 'a/0030')
  ->addMetadata([
    'title' => ts('Mapping Type'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value', 'weight'], [
    [ts('Search Builder'), 'Search Builder', 1, 1],
    [ts('Import Contact'), 'Import Contact', 2, 2],
    [ts('Import Activity'), 'Import Activity', 3, 3],
    [ts('Import Contribution'), 'Import Contribution', 4, 4],
    [ts('Import Membership'), 'Import Membership', 5, 5],
    [ts('Import Participant'), 'Import Participant', 6, 6],
    [ts('Export Contact'), 'Export Contact', 7, 7],
    [ts('Export Contribution'), 'Export Contribution', 8, 8],
    [ts('Export Membership'), 'Export Membership', 9, 9],
    [ts('Export Participant'), 'Export Participant', 10, 10],
    [ts('Export Pledge'), 'Export Pledge', 11, 11],
    [ts('Export Case'), 'Export Case', 12, 12],
    [ts('Export Activity'), 'Export Activity', 14, 14],
  ])
  ->addDefaults([
    'is_reserved' => 1,
  ]);
