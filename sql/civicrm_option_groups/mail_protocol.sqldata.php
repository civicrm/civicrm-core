<?php
return CRM_Core_CodeGen_OptionGroup::create('mail_protocol', 'a/0036')
  ->addMetadata([
    'title' => ts('Mail Protocol'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    ['IMAP', 'IMAP', 1],
    ['Maildir', 'Maildir', 2],
    ['POP3', 'POP3', 3],
    ['Localdir', 'Localdir', 4],
  ]);
