<?php
return CRM_Core_CodeGen_OptionGroup::create('gender', 'a/0003')
  ->addMetadata([
    'title' => ts('Gender'),
    'description' => ts('CiviCRM is pre-configured with standard options for individual gender (Male, Female, Other). Modify these options as needed for your installation.'),
    'data_type' => 'Integer',
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Female'), 'Female', 1],
    [ts('Male'), 'Male', 2],
    [ts('Other'), 'Other', 3],
  ]);
