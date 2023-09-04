<?php
return CRM_Core_CodeGen_OptionGroup::create('pcp_owner_notify', 'a/0013')
  ->addMetadata([
    'title' => ts('PCP owner notifications'),
    'is_locked' => 1,
  ])
  ->addValues([
    [
      'label' => ts('Owner chooses whether to receive notifications'),
      'value' => 1,
      'name' => 'owner_chooses',
      'is_default' => 1,
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Notifications are sent to ALL owners'),
      'value' => 2,
      'name' => 'all_owners',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Notifications are NOT available'),
      'value' => 3,
      'name' => 'no_notifications',
      'is_reserved' => 1,
    ],
  ]);
