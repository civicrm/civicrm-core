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
