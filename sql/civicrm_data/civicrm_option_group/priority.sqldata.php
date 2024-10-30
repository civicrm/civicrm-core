<?php
return CRM_Core_CodeGen_OptionGroup::create('priority', 'a/0037')
  ->addMetadata([
    'title' => ts('Priority'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Urgent'), 'Urgent', 1],
    [ts('Normal'), 'Normal', 2],
    [ts('Low'), 'Low', 3],
  ]);
