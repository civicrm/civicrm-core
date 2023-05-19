<?php
return CRM_Core_CodeGen_OptionGroup::create('contact_date_reminder_options', 'a/0076')
  ->addMetadata([
    'title' => ts('Contact Date Reminder Options'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Actual date only'), 'Actual date only', 1, 'is_reserved' => 1],
    [ts('Each anniversary'), 'Each anniversary', 2, 'is_reserved' => 1],
  ])
  ->addDefaults([
    'filter' => NULL,
  ]);
