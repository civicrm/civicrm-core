<?php
return CRM_Core_CodeGen_BounceType::create('Host')
  ->addMetadata([
    // FIXME: Typo
    'description' => ts('Unable to deliver to destintation mail server'),
    // 'description' => ts('Unable to deliver to destination mail server'),
    'hold_threshold' => 3,
  ])
  ->addValueTable(['pattern'], [
    ['(unknown|not local) host'],
    ['all hosts have been failing'],
    ['allowed rcpthosts'],
    ['connection (refused|timed out)'],
    ['not connected'],
    ['couldn\'t find any host named'],
    ['error involving remote host'],
    ['host unknown'],
    ['invalid host name'],
    ['isn\'t in my control/locals file'],
    ['local configuration error'],
    ['not a gateway'],
    ['server is (down or unreachable|not responding)'],
    ['too many connections'],
    ['unable to connect'],
    ['lost connection'],
    ['conversation with [^ ]* timed out while'],
    ['server requires authentication'],
    ['authentication (is )?required'],
  ]);
