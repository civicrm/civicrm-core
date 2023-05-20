<?php
return CRM_Core_CodeGen_BounceType::create('Relay')
  ->addMetadata([
    'description' => ts('Unable to reach destination mail server'),
    'hold_threshold' => 3,
  ])
  ->addValueTable(['pattern'], [
    ['cannot find your hostname'],
    ['ip name lookup'],
    ['not configured to relay mail'],
    ['relay(ing)? (not permitted|(access )?denied)'],
    ['relayed mail to .+? not allowed'],
    ['sender ip must resolve'],
    ['unable to relay'],
    ['No route to host'],
    ['Network is unreachable'],
    ['unrouteable address'],
    ['We don.t handle mail for'],
    ['we do not relay'],
    ['Rejected by next-hop'],
    ['not permitted to( *550)? relay through this server'],
  ]);
