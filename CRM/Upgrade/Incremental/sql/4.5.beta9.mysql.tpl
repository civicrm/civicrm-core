{* file to handle db changes in 4.5.beta9 during upgrade *}

-- CRM-15211
UPDATE `civicrm_dashboard` SET `permission` = 'access my cases and activities,access all cases and activities', `permission_operator` = 'OR' WHERE `name` = 'casedashboard';

-- CRM-15218
UPDATE `civicrm_uf_group` SET name = LOWER(name) WHERE name IN ("New_Individual", "New_Organization", "New_Household");

-- CRM-15220
UPDATE `civicrm_navigation` SET url = 'civicrm/admin/options/grant_type?reset=1' WHERE url = 'civicrm/admin/options/grant_type&reset=1';

SELECT @parent_id := `id` FROM `civicrm_navigation` WHERE `name` = 'CiviGrant' AND `domain_id` = {$domainID};
INSERT INTO civicrm_navigation
( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
( {$domainID}, 'civicrm/admin/options/grant_status?reset=1', '{ts escape="sql" skip="true"}Grant Status{/ts}', 'Grant Status', 'access CiviGrant,administer CiviCRM', 'AND', @parent_id, '1', NULL, 2 );

-- CRM-14853
UPDATE civicrm_financial_trxn cft
INNER JOIN ( SELECT max(cft.id) trxn_id, ceft.entity_id contribution_id
FROM civicrm_financial_trxn cft
LEFT JOIN civicrm_entity_financial_trxn ceft ON ceft.financial_trxn_id = cft.id
WHERE ceft.entity_table = 'civicrm_contribution'
GROUP BY ceft.entity_id ) as temp ON temp.trxn_id = cft.id
INNER JOIN civicrm_contribution cc ON cc.id = temp.contribution_id
AND cc.payment_instrument_id <> cft.payment_instrument_id
SET cft.payment_instrument_id = cc.payment_instrument_id;

--CRM-15086
SELECT @option_group_id_batch_status := id FROM civicrm_option_group WHERE name = 'batch_status';

UPDATE civicrm_option_value SET name = 'Open' WHERE value = 1 AND option_group_id = @option_group_id_batch_status;
UPDATE civicrm_option_value SET name = 'Closed' WHERE value = 2 AND option_group_id = @option_group_id_batch_status;
UPDATE civicrm_option_value SET name = 'Data Entry' WHERE value = 3 AND option_group_id = @option_group_id_batch_status;
UPDATE civicrm_option_value SET name = 'Reopened' WHERE value = 4 AND option_group_id = @option_group_id_batch_status;
UPDATE civicrm_option_value SET name = 'Exported' WHERE value = 5 AND option_group_id = @option_group_id_batch_status;
