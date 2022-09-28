<?php
return [
  'civicrm_campaign' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this Campaign enabled or disabled/cancelled?'",
  ],
  'civicrm_survey' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this survey enabled or disabled/cancelled?'",
    'is_default' => "DEFAULT 0 COMMENT 'Is this default survey?'",
    'bypass_confirm' => "DEFAULT 0 COMMENT 'Bypass the email verification.'",
    'is_share' => "DEFAULT 1 COMMENT 'Can people share the petition through social media?'",
  ],
];
