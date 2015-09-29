{* file to handle db changes in 4.5.3 during upgrade *}

-- CRM-15475
SELECT @membershipStatusId := id FROM civicrm_membership_status WHERE name = 'Cancelled';
SELECT @membershipStatusWeight := max(weight) + 1 FROM civicrm_membership_status;

INSERT INTO civicrm_membership_status (id, name, {localize field='label'}label{/localize}, start_event, start_event_adjust_unit, start_event_adjust_interval, end_event, end_event_adjust_unit, end_event_adjust_interval, is_current_member, is_admin, weight, is_default, is_active, is_reserved)
VALUES (@membershipStatusId, 'Cancelled', {localize}'{ts escape="sql"}Cancelled{/ts}'{/localize}, 'join_date', null, null, 'join_date', null, null, 0, 0, @membershipStatusWeight, 0, 0, 1) 
ON DUPLICATE KEY UPDATE is_reserved = 1;

-- CRM-15558
ALTER TABLE `civicrm_mailing_bounce_type` CHANGE `name` `name` VARCHAR( 24 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'Type of bounce';