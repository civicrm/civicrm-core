{* file to handle db changes in 4.3.4 during upgrade*}

-- CRM-12466
INSERT INTO
civicrm_option_group (name, {localize field='title'}title{/localize}, is_reserved, is_active)
VALUES
('contact_smart_group_display', {localize}'{ts escape="sql"}Contact Smart Group View Options{/ts}'{/localize}, 1, 1);

SELECT @option_group_id_csgOpt := max(id) FROM civicrm_option_group WHERE name = 'contact_smart_group_display';

INSERT INTO
civicrm_option_value (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter,
is_default, weight)
VALUES
(@option_group_id_csgOpt, {localize}'Show Smart Groups on Demand'{/localize}, 1, 'showondemand', NULL, 0, 0, 1),
(@option_group_id_csgOpt, {localize}'Always Show Smart Groups'{/localize}, 2, 'alwaysshow', NULL, 0, 0, 2),
(@option_group_id_csgOpt, {localize}'Hide Smart Groups'{/localize}, 3, 'hide' , NULL, 0, 0, 3);


INSERT INTO civicrm_setting
(domain_id, contact_id, is_domain, group_name, name, value)
VALUES
({$domainID}, NULL, 1, 'CiviCRM Preferences', 'contact_smart_group_display', '{serialize}1{/serialize}');

-- CRM-12470
UPDATE civicrm_financial_account
SET is_default = 1
WHERE name IN ('{ts escape="sql"}Premiums{/ts}', '{ts escape="sql"}Banking Fees{/ts}', '{ts escape="sql"}Accounts Payable{/ts}', '{ts escape="sql"}Donation{/ts}');

-- CRM-12665 remove options groups
DELETE cov, cog FROM civicrm_option_group cog
INNER JOIN civicrm_option_value cov ON cov.option_group_id = cog.id
WHERE cog.name IN ('grant_program_status', 'allocation_algorithm');