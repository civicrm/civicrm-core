<?php
return CRM_Core_CodeGen_OptionGroup::create('auto_renew_options', 'a/0069')
  ->addMetadata([
    'title' => ts('Auto Renew Options'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Renewal Reminder (non-auto-renew memberships only)'), 'Renewal Reminder (non-auto-renew memberships only)', 1],
    [ts('Auto-renew Memberships Only'), 'Auto-renew Memberships Only', 2],
    [ts('Reminder for Both'), 'Reminder for Both', 3],
  ])
  ->addDefaults([
    'is_default' => NULL,
  ]);
