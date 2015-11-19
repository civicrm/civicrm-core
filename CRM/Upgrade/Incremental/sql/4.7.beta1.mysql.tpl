{* file to handle db changes in 4.7.beta1 during upgrade *}

SELECT @parent_id := id from `civicrm_navigation` where name = 'Administration Console' AND domain_id = {$domainID};
INSERT INTO civicrm_navigation
( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
( {$domainID}, 'civicrm/a/#/status', '{ts escape="sql" skip="true"}System Status{/ts}', 'System Status', 'administer CiviCRM', '', @parent_id, '1', NULL, 0 );

UPDATE civicrm_contact SET is_deceased = 0 WHERE is_deceased IS NULL;

-- CRM-17503 PayPal Express processor type can support recurring payments
UPDATE civicrm_payment_processor_type pp
LEFT JOIN civicrm_payment_processor p ON p.payment_processor_type_id = pp.id
SET pp.is_recur = 1, p.is_recur = 1
WHERE pp.name='PayPal_Express';

UPDATE civicrm_preferences_date SET description = '{ts escape="sql"}Used in search forms and for relationships.{/ts}'
WHERE name = 'searchDate';

--CRM-16761 Self service cancel or transfer for events
ALTER TABLE civicrm_event
  ADD COLUMN selfcancelxfer_time INT(10) unsigned DEFAULT 0 COMMENT 'Number of hours prior to event start date to allow self-service cancellation or transfer.',
  ADD COLUMN allow_selfcancelxfer TINYINT(4) DEFAULT '0' COMMENT 'Allow self service cancellation or transfer for event?';
ALTER TABLE civicrm_participant
  ADD COLUMN transferred_to_contact_id INT(10) unsigned DEFAULT NULL COMMENT 'Contact to which the event registration is transferred',
  ADD CONSTRAINT `FK_civicrm_participant_transferred_to_contact_id` FOREIGN KEY (`transferred_to_contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL;

INSERT INTO civicrm_participant_status_type(name, {localize field='label'}label{/localize}, class, is_reserved, is_active, is_counted, weight, visibility_id)
VALUES ('Transferred', '{ts escape="sql"}Transferred{/ts}', 'Negative', 1, 1, 0, 16, 2);
