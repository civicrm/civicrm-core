<?php
return CRM_Core_CodeGen_OptionGroup::create('cg_extend_objects', 'a/0056')
  ->addMetadata([
    'title' => ts('Objects a custom group extends to'),
  ])
  ->addValues(['label', 'name', 'value'], [
    [ts('Survey'), 'civicrm_survey', 'Survey'],
    [ts('Cases'), 'civicrm_case', 'Case', 'grouping' => 'case_type_id'],
  ]);
