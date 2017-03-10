{* file to handle db changes in 4.7.beta1 during upgrade *}

-- CRM-16901 Recurring contributions summary report template
SELECT @option_group_id_report := max(id) from civicrm_option_group where name = 'report_template';
SELECT @contributeCompId := max(id) FROM civicrm_component where name = 'CiviContribute';
INSERT INTO
   civicrm_option_value (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight, {localize field='description'}description{/localize}, is_optgroup, is_reserved, is_active, component_id, visibility_id)
VALUES
   (@option_group_id_report, {localize}'{ts escape="sql"}Recurring Contributions Summary{/ts}'{/localize}, 'contribute/recursummary', 'CRM_Report_Form_Contribute_RecurSummary',               NULL, 0, NULL, 49, {localize}'{ts escape="sql"}Provides simple summary for each payment instrument for which there are recurring contributions (e.g. Credit Card, Standing Order, Direct Debit etc.), showing within a given date range.{/ts}'{/localize}, 0, 0, 1, @contributeCompId, NULL);

SELECT @parent_id := id from civicrm_navigation where name = 'Administration Console' AND domain_id = {$domainID};
INSERT INTO civicrm_navigation
( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
( {$domainID}, 'civicrm/a/#/status', '{ts escape="sql" skip="true"}System Status{/ts}', 'System Status', 'administer CiviCRM', '', @parent_id, '1', NULL, 0 );

UPDATE civicrm_contact SET is_deceased = 0 WHERE is_deceased IS NULL;

-- CRM-16597
UPDATE civicrm_option_value SET {localize field="label"}label = '{ts escape="sql"}Pledge Detail Report{/ts}'{/localize}, {localize field="description"}description = '{ts escape="sql"}List of pledges including amount pledged, pledge status, next payment date, balance due, total amount paid etc.{/ts}'{/localize} WHERE option_group_id = @option_group_id_report AND name = 'CRM_Report_Form_Pledge_Detail';

UPDATE civicrm_option_value SET {localize field="description"}description = '{ts escape="sql"}Groups and totals pledges by criteria including contact, time period, pledge status, location, etc.{/ts}'{/localize} WHERE option_group_id = @option_group_id_report AND name = 'CRM_Report_Form_Pledge_Summary';

UPDATE civicrm_report_instance SET title = '{ts escape="sql"}Pledge Detail{/ts}', description = '{ts escape="sql"}List of pledges including amount pledged, pledge status, next payment date, balance due, total amount paid etc.{/ts}' WHERE report_id = 'pledge/detail';

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
VALUES ('Transferred', {localize}'{ts escape="sql"}Transferred{/ts}'{/localize}, 'Negative', 1, 1, 0, 16, 2);

{include file='../CRM/Upgrade/4.7.beta1.msg_template/civicrm_msg_template.tpl'}

-- CRM-16259 Added is_payment flag
ALTER TABLE civicrm_financial_trxn ADD COLUMN is_payment tinyint(4) DEFAULT '0' COMMENT 'Is this entry either a payment or a reversal of a payment?';
