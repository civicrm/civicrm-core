<?php
return CRM_Core_CodeGen_OptionGroup::create('case_status', 'a/0026')
  ->addMetadata([
    'title' => ts('Case Status'),
    'option_value_fields' => 'name,label,description,color',
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Ongoing'), 'Open', 1, 'grouping' => 'Opened', 'is_default' => 1, 'is_reserved' => 1],
    [ts('Resolved'), 'Closed', 2, 'grouping' => 'Closed', 'is_reserved' => 1],
    [ts('Urgent'), 'Urgent', 3, 'grouping' => 'Opened'],
  ]);
