<?php
return CRM_Core_CodeGen_OptionGroup::create('mail_protocol', 'a/0036')
  ->addMetadata([
    'title' => ts('Mail Protocol'),
  ])
  ->addValueTable(['name', 'value'], [
    ['IMAP', 1],
    ['Maildir', 2],
    ['POP3', 3],
    ['Localdir', 4],
  ])
  ->syncColumns('fill', ['name' => 'label']);
