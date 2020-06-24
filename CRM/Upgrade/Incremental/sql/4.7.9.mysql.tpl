{* file to handle db changes in 4.7.9 during upgrade *}

-- CRM-17607 Change PDF activity type label
SELECT @option_group_id_act := max(id) from civicrm_option_group where name = 'activity_type';
UPDATE civicrm_option_value SET
  {localize field="label"}label = '{ts escape="sql"}Print/Merge Document{/ts}'{/localize},
  {localize field="description"}description = '{ts escape="sql"}Export letters and other printable documents.{/ts}'{/localize}
WHERE name = 'Print PDF Letter' AND option_group_id = @option_group_id_act;

-- CRM-18699 Fix Wake Island misspelling, was Wake Ialand
UPDATE civicrm_state_province SET name="Wake Island" WHERE name="Wake Ialand";

-- CRM-18960 Change title in Getting Started widget
UPDATE civicrm_dashboard SET
  {localize field="label"}label = '{ts escape="sql"}CiviCRM Resources{/ts}'{/localize}
WHERE name = 'getting-started';

-- CRM-16189
ALTER TABLE civicrm_financial_account
 ADD `opening_balance` decimal(20,2) DEFAULT '0.00' COMMENT 'Contains the opening balance for this financial account',
 ADD `current_period_opening_balance` decimal(20,2) DEFAULT '0.00' COMMENT 'Contains the opening balance for the current period for this financial account';

ALTER TABLE civicrm_contribution
ADD `revenue_recognition_date` datetime DEFAULT NULL COMMENT 'Stores the date when revenue should be recognized.';

-- CRM-16189 Financial account relationship
SELECT @option_group_id_arel := max(id) from civicrm_option_group where name = 'account_relationship';
SELECT @option_group_id_arel_wt  := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel;
SELECT @option_group_id_arel_val := MAX(CAST( `value` AS UNSIGNED )) FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel;

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
(@option_group_id_arel, {localize}'{ts escape="sql"}Deferred Revenue Account is{/ts}'{/localize}, @option_group_id_arel_val+1, 'Deferred Revenue Account is', NULL, 0, 0, @option_group_id_arel_wt+1, {localize}'{ts escape="sql"}Deferred Revenue Account is{/ts}'{/localize}, 0, 1, 1, 2, NULL);

SELECT @option_group_id_fat := max(id) from civicrm_option_group where name = 'financial_account_type';
SELECT @opLiability := value FROM civicrm_option_value WHERE name = 'Liability' and option_group_id = @option_group_id_fat;
SELECT @domainContactId := contact_id from civicrm_domain where id = {$domainID};
INSERT IGNORE INTO
  `civicrm_financial_account` (`name`, `contact_id`, `financial_account_type_id`, `description`, `accounting_code`, `account_type_code`, `is_reserved`, `is_active`, `is_deductible`, `is_default`)
VALUES
  ('Deferred Revenue - Event Fee', @domainContactId, @opLiability, 'Event revenue to be recognized in future months when the events occur', '2730', 'OCLIAB', 0, 1, 0, 0),
  ('Deferred Revenue - Member Dues', @domainContactId, @opLiability, 'Membership revenue to be recognized in future months', '2740', 'OCLIAB', 0, 1, 0, 0);

