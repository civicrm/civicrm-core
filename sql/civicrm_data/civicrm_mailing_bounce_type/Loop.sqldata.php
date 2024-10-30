<?php
return CRM_Core_CodeGen_BounceType::create('Loop')
  ->addMetadata([
    'description' => ts('Mail routing error'),
    'hold_threshold' => 3,
  ])
  ->addValueTable(['pattern'], [
    ['(mail( forwarding)?|routing).loop'],
    ['excessive recursion'],
    ['loop detected'],
    ['maximum hop count exceeded'],
    ['message was forwarded more than the maximum allowed times'],
    ['too many (hops|recursive forwards)'],
  ]);
