<?php
return CRM_Core_CodeGen_BounceType::create('Away')
  ->addMetadata([
    'description' => ts('Recipient is on vacation'),
    'hold_threshold' => 30,
  ])
  ->addValueTable(['pattern'], [
    ['(be|am)? (out of|away from) (the|my)? (office|computer|town)'],
    ['i am on vacation'],
  ]);
