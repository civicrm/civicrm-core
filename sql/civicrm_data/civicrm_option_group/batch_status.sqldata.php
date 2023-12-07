<?php
return CRM_Core_CodeGen_OptionGroup::create('batch_status', 'a/0066')
  ->addMetadata([
    'title' => ts('Batch Status'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Open'), 'Open', 1],
    [ts('Closed'), 'Closed', 2],
    [ts('Data Entry'), 'Data Entry', 3],
    [ts('Reopened'), 'Reopened', 4],
    [ts('Exported'), 'Exported', 5],
  ]);
