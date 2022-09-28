<?php
return [
    // No change in DAO?
  'civicrm_event' => [
    'is_public' => "DEFAULT 1 COMMENT 'Public events will be included in the iCal feeds. Access to private event information may be limited using ACLs.'",
    'is_online_registration' => "DEFAULT 0 COMMENT 'If true, include registration link on Event Info page.'",
    'is_monetary' => "DEFAULT 0 COMMENT 'If true, one or more fee amounts must be set and a Payment Processor must be configured for Online Event Registration.'",
    'is_map' => "DEFAULT 0 COMMENT 'Include a map block on the Event Information page when geocode info is available and a mapping provider has been specified?'",
    'is_active' => "DEFAULT 0 COMMENT 'Is this Event enabled or disabled/cancelled?'",
    'is_show_location' => "DEFAULT 1 COMMENT 'If true, show event location.'",
    'is_email_confirm' => "DEFAULT 0 COMMENT 'If true, confirmation is automatically emailed to contact on successful registration.'",
    'is_pay_later' => "DEFAULT 0 COMMENT 'if true - allows the user to send payment directly to the org later'",
    'is_partial_payment' => "DEFAULT 0 COMMENT 'is partial payment enabled for this event'",
    'is_multiple_registrations' => "DEFAULT 0 COMMENT 'if true - allows the user to register multiple participants for event'",
    'allow_same_participant_emails' => "DEFAULT 0 COMMENT 'if true - allows the user to register multiple registrations from same email address.'",
    'has_waitlist' => "DEFAULT 0 COMMENT 'Whether the event has waitlist support.'",
    'requires_approval' => "DEFAULT 0 COMMENT 'Whether participants require approval before they can finish registering.'",
    'allow_selfcancelxfer' => "DEFAULT 0 COMMENT 'Allow self service cancellation or transfer for event?'",
    'is_template' => "DEFAULT 0 COMMENT 'whether the event has template'",
    'is_share' => "DEFAULT 1 COMMENT 'Can people share the event through social media?'",
    'is_confirm_enabled' => "DEFAULT 1 COMMENT 'If false, the event booking confirmation screen gets skipped'",
    'is_billing_required' => "DEFAULT 0 COMMENT 'if true than billing block is required this event'",
  ],
  'civicrm_event_carts' => [
    'completed' => "DEFAULT 0",
  ],
  'civicrm_participant' => [
    'is_test' => "DEFAULT 0",
    'is_pay_later' => "DEFAULT 0",
  ],
  'civicrm_participant_status_type' => [
    'is_reserved' => "DEFAULT 0 COMMENT 'whether this is a status type required by the system'",
    'is_active' => "DEFAULT 1 COMMENT 'whether this status type is active'",
    'is_counted' => "DEFAULT 0 COMMENT 'whether this status type is counted against event size limit'",
  ],
];
