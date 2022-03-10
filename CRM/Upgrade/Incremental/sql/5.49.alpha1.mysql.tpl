{* file to handle db changes in 5.49.alpha1 during upgrade *}

UPDATE `civicrm_event` SET is_public = 0 WHERE is_public IS NULL;
UPDATE `civicrm_event` SET is_online_registration = 0 WHERE is_online_registration IS NULL;
UPDATE `civicrm_event` SET is_monetary = 0 WHERE is_monetary IS NULL;
UPDATE `civicrm_event` SET is_map = 0 WHERE is_map IS NULL;
UPDATE `civicrm_event` SET is_active = 0 WHERE is_active IS NULL;
UPDATE `civicrm_event` SET is_show_location = 0 WHERE is_show_location IS NULL;
UPDATE `civicrm_event` SET is_email_confirm = 0 WHERE is_email_confirm IS NULL;
UPDATE `civicrm_event` SET is_pay_later = 0 WHERE is_pay_later IS NULL;
UPDATE `civicrm_event` SET is_partial_payment = 0 WHERE is_partial_payment IS NULL;
UPDATE `civicrm_event` SET is_multiple_registrations = 0 WHERE is_multiple_registrations IS NULL;
UPDATE `civicrm_event` SET allow_same_participant_emails = 0 WHERE allow_same_participant_emails IS NULL;
UPDATE `civicrm_event` SET has_waitlist = 0 WHERE has_waitlist IS NULL;
UPDATE `civicrm_event` SET requires_approval = 0 WHERE requires_approval IS NULL;
UPDATE `civicrm_event` SET allow_selfcancelxfer = 0 WHERE allow_selfcancelxfer IS NULL;
UPDATE `civicrm_event` SET is_template = 0 WHERE is_template IS NULL;
UPDATE `civicrm_event` SET is_share = 0 WHERE is_share IS NULL;
UPDATE `civicrm_event` SET is_confirm_enabled = 0 WHERE is_confirm_enabled IS NULL;
UPDATE `civicrm_event` SET is_billing_required = 0 WHERE is_billing_required IS NULL;
ALTER TABLE `civicrm_event`
  CHANGE `is_public` `is_public` tinyint NOT NULL DEFAULT 1 COMMENT 'Public events will be included in the iCal feeds. Access to private event information may be limited using ACLs.',
  CHANGE `is_online_registration` `is_online_registration` tinyint NOT NULL DEFAULT 0 COMMENT 'If true, include registration link on Event Info page.',
  CHANGE `is_monetary` `is_monetary` tinyint NOT NULL DEFAULT 0 COMMENT 'If true, one or more fee amounts must be set and a Payment Processor must be configured for Online Event Registration.',
  CHANGE `is_map` `is_map` tinyint NOT NULL DEFAULT 0 COMMENT 'Include a map block on the Event Information page when geocode info is available and a mapping provider has been specified?',
  CHANGE `is_active` `is_active` tinyint NOT NULL DEFAULT 0 COMMENT 'Is this Event enabled or disabled/cancelled?',
  CHANGE `is_show_location` `is_show_location` tinyint NOT NULL DEFAULT 1 COMMENT 'If true, show event location.',
  CHANGE `is_email_confirm` `is_email_confirm` tinyint NOT NULL DEFAULT 0 COMMENT 'If true, confirmation is automatically emailed to contact on successful registration.',
  CHANGE `is_pay_later` `is_pay_later` tinyint NOT NULL DEFAULT 0 COMMENT 'if true - allows the user to send payment directly to the org later',
  CHANGE `is_partial_payment` `is_partial_payment` tinyint NOT NULL DEFAULT 0 COMMENT 'is partial payment enabled for this event',
  CHANGE `is_multiple_registrations` `is_multiple_registrations` tinyint NOT NULL DEFAULT 0 COMMENT 'if true - allows the user to register multiple participants for event',
  CHANGE `allow_same_participant_emails` `allow_same_participant_emails` tinyint NOT NULL DEFAULT 0 COMMENT 'if true - allows the user to register multiple registrations from same email address.',
  CHANGE `has_waitlist` `has_waitlist` tinyint NOT NULL DEFAULT 0 COMMENT 'Whether the event has waitlist support.',
  CHANGE `requires_approval` `requires_approval` tinyint NOT NULL DEFAULT 0 COMMENT 'Whether participants require approval before they can finish registering.',
  CHANGE `allow_selfcancelxfer` `allow_selfcancelxfer` tinyint NOT NULL DEFAULT 0 COMMENT 'Allow self service cancellation or transfer for event?',
  CHANGE `is_template` `is_template` tinyint NOT NULL DEFAULT 0 COMMENT 'whether the event has template',
  CHANGE `is_share` `is_share` tinyint NOT NULL DEFAULT 1 COMMENT 'Can people share the event through social media?',
  CHANGE `is_confirm_enabled` `is_confirm_enabled` tinyint NOT NULL DEFAULT 1 COMMENT 'If false, the event booking confirmation screen gets skipped',
  CHANGE `is_billing_required` `is_billing_required` tinyint NOT NULL DEFAULT 0 COMMENT 'if true than billing block is required this event';

UPDATE `civicrm_contribution` SET is_template = 0 WHERE is_template IS NULL;
UPDATE `civicrm_contribution` SET is_test = 0 WHERE is_test IS NULL;
UPDATE `civicrm_contribution` SET is_pay_later = 0 WHERE is_pay_later IS NULL;
ALTER TABLE `civicrm_contribution`
  CHANGE `is_test` `is_test` tinyint NOT NULL DEFAULT 0,
  CHANGE `is_pay_later` `is_pay_later` tinyint NOT NULL DEFAULT 0,
  CHANGE `is_template` `is_template` tinyint NOT NULL DEFAULT 0 COMMENT 'Shows this is a template for recurring contributions.';

