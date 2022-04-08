<?php
return [
  'civicrm_mailing' => [
    'url_tracking' => "DEFAULT 0 COMMENT 'Should we track URL click-throughs for this mailing?'",
    'forward_replies' => "DEFAULT 0 COMMENT 'Should we forward replies back to the author?'",
    'auto_responder' => "DEFAULT 0 COMMENT 'Should we enable the auto-responder?'",
    'open_tracking' => "DEFAULT 0 COMMENT 'Should we track when recipients open/read this mailing?'",
    'is_completed' => "DEFAULT 0 COMMENT 'Has at least one job associated with this mailing finished?'",
    'override_verp' => "DEFAULT 0 COMMENT 'Overwrite the VERP address in Reply-To'",
    'is_archived' => "DEFAULT 0 COMMENT 'Is this mailing archived?'",
    'dedupe_email' => "DEFAULT 0 COMMENT 'Remove duplicate emails?'",
  ],
  'civicrm_mailing_job' => [
    'is_test' => "DEFAULT 0 COMMENT 'Is this job for a test mail?'",
  ],
  'civicrm_mailing_component' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this property active?'",
  ],
];
