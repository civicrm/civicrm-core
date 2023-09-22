<?php
return [
  'civicrm_pcp' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is Personal Campaign Page enabled/active?'",
    'is_notify' => "DEFAULT 0 COMMENT 'Notify owner via email when someone donates to page?'",
  ],
  'civicrm_pcp_block' => [
    'is_approval_needed' => "DEFAULT 0 COMMENT 'Does Personal Campaign Page require manual activation by administrator? (is inactive by default after setup)?'",
    'is_tellfriend_enabled' => "DEFAULT 0 COMMENT 'Does Personal Campaign Page allow using tell a friend?'",
    'is_active' => "DEFAULT 1 COMMENT 'Is Personal Campaign Page Block enabled/active?'",
  ],

];
