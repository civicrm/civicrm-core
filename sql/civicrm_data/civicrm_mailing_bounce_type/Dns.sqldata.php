<?php
return CRM_Core_CodeGen_BounceType::create('Dns')
  ->addMetadata([
    'description' => ts('Unable to resolve recipient domain'),
    'hold_threshold' => 3,
  ])
  ->addValueTable(['pattern'], [
    ['name(server entry| lookup failure)'],
    ['no (mail server|matches to nameserver query|dns entries)'],
    ['reverse dns entry'],
    ['Host or domain name not found'],
    ['Unable to resolve MX record for'],
  ]);
