<?php
return CRM_Core_CodeGen_BounceType::create('Syntax')
  ->addMetadata([
    'description' => ts('Error in SMTP transaction'),
    'hold_threshold' => 3,
  ])
  ->addValueTable(['pattern'], [
    ['nonstandard smtp line terminator'],
    ['syntax error in from address'],
    ['unknown smtp code'],
  ]);
