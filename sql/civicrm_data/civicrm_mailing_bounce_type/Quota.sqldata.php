<?php
return CRM_Core_CodeGen_BounceType::create('Quota')
  ->addMetadata([
    'description' => ts('User inbox is full'),
    'hold_threshold' => 3,
  ])
  ->addValueTable(['pattern'], [
    ['(disk(space)?|over the allowed|exceed(ed|s)?|storage) quota'],
    ['522_mailbox_full'],
    ['exceeds allowed message count'],
    ['file too large'],
    ['full mailbox'],
    ['(mail|in)(box|folder) ((for user \\w+ )?is )?full'],
    ['mailbox (has exceeded|is over) the limit'],
    ['mailbox( exceeds allowed)? size'],
    ['no space left for this user'],
    ['over\\s?quota'],
    ['quota (for the mailbox )?has been exceeded'],
    ['quota ?(usage|violation|exceeded)'],
    ['recipient storage full'],
    ['not able to receive more mail'],
    ['doesn.t have enough disk space left'],
    ['exceeded storage allocation'],
    ['running out of disk space'],
  ]);
