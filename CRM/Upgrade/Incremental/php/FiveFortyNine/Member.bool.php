<?php
return [
  'civicrm_membership' => [
    'is_override' => "DEFAULT 0 COMMENT 'Admin users may set a manual status which overrides the calculated status. When this flag is true, automated status update scripts should NOT modify status for the record.'",
    'is_test' => "DEFAULT 0",
    'is_pay_later' => "DEFAULT 0",
  ],
  'civicrm_membership_block' => [
    'display_min_fee' => "DEFAULT 1 COMMENT 'Display minimum membership fee'",
    'is_separate_payment' => "DEFAULT 1 COMMENT 'Should membership transactions be processed separately'",
    'is_required' => "DEFAULT 0 COMMENT 'Is membership sign up optional'",
    'is_active' => "DEFAULT 1 COMMENT 'Is this membership_block enabled'",
  ],
  'civicrm_membership_status' => [
    'is_current_member' => "DEFAULT 0 COMMENT 'Does this status aggregate to current members (e.g. New, Renewed, Grace might all be TRUE... while Unrenewed, Lapsed, Inactive would be FALSE).'",
    'is_admin' => "DEFAULT 0 COMMENT 'Is this status for admin/manual assignment only.'",
    'is_default' => "DEFAULT 0 COMMENT 'Assign this status to a membership record if no other status match is found.'",
    'is_active' => "DEFAULT 1 COMMENT 'Is this membership_status enabled.'",
    'is_reserved' => "DEFAULT 0 COMMENT 'Is this membership_status reserved.'",
  ],
];
