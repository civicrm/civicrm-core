<?php
return CRM_Core_CodeGen_OptionGroup::create('priority', 'a/0037')
  ->addMetadata([
    'title' => ts('Priority'),
  ])
  ->addValueTable(['label', 'name', 'value', 'is_default'], [
    [ts('Urgent'), 'Urgent', 1, 0],
    [ts('Normal'), 'Normal', 2, 1],
    [ts('Low'), 'Low', 3, 0],
  ]);
