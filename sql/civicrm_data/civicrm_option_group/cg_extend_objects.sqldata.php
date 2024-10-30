<?php
return CRM_Core_CodeGen_OptionGroup::create('cg_extend_objects', 'a/0056')
  ->addMetadata([
    'title' => ts('Objects a custom group extends to'),
  ])
  ->addValues([
    [
      'label' => ts('Survey'),
      'value' => 'Survey',
      'name' => 'civicrm_survey',
    ],
    [
      'label' => ts('Cases'),
      'value' => 'Case',
      'name' => 'civicrm_case',
      'grouping' => 'case_type_id',
    ],
  ]);
