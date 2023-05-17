<?php
return CRM_Core_CodeGen_OptionGroup::create('pcp_owner_notify', 'a/0013')
  ->addMetadata([
    'title' => ts('PCP owner notifications'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Owner chooses whether to receive notifications'), 'owner_chooses', 1, 'is_default' => 1, 'is_reserved' => 1],
    [ts('Notifications are sent to ALL owners'), 'all_owners', 2, 'is_reserved' => 1],
    [ts('Notifications are NOT available'), 'no_notifications', 3, 'is_reserved' => 1],
  ]);
