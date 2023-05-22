<?php
return CRM_Core_CodeGen_BounceType::create('Inactive')
  ->addMetadata([
    'description' => ts('User account is no longer active'),
    'hold_threshold' => 1,
  ])
  ->addValueTable(['pattern'], [
    ['(my )?e-?mail( address)? has changed'],
    ['account (inactive|expired|deactivated)'],
    ['account is locked'],

    // FIXME: In the old SQL, the "\" in "\w" didn't get through. We're probably loading the wrong data.
    // ['changed \w+( e-?mail)? address'],
    ['changed w+( e-?mail)? address'],

    ['deactivated mailbox'],
    ['disabled or discontinued'],
    ['inactive user'],
    ['is inactive on this domain'],
    ['mail receiving disabled'],
    ['mail( ?)address is administrative?ly disabled'],
    ['mailbox (temporarily disabled|currently suspended)'],
    ['no longer (accepting mail|on server|in use|with|employed|on staff|works for|using this account)'],
    ['not accepting (mail|messages)'],
    ['please use my new e-?mail address'],
    ['this address no longer accepts mail'],
    ['user account suspended'],
    ['account that you tried to reach is disabled'],
    ['User banned'],
  ]);
