<?php
return CRM_Core_CodeGen_BounceType::create('AOL')
  ->addMetadata([
    'description' => ts('AOL Terms of Service complaint'),
    'hold_threshold' => 1,
  ])
  ->addValueTable(['pattern'], [
    ['Client TOS Notification'],
  ]);
