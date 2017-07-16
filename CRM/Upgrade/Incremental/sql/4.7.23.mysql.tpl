{* file to handle db changes in 4.7.23 during upgrade *}

{include file='../CRM/Upgrade/4.7.23.msg_template/civicrm_msg_template.tpl'}

-- CRM-20816: Add CiviCase settings

SELECT @civicaseAdminId := id FROM civicrm_navigation WHERE name = 'CiviCase' AND domain_id = {$domainID};

INSERT INTO civicrm_navigation
(domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight)
VALUES
({$domainID}, 'civicrm/admin/setting/case?reset=1', '{ts escape="sql" skip="true"}CiviCase Settings{/ts}', 'CiviCase Settings', NULL, 'AND', @civicaseAdminId, '1', NULL, 1);

-- CRM-20387
UPDATE `civicrm_contribution` SET `invoice_number` = `invoice_id` WHERE `invoice_id` LIKE CONCAT('%', `id`);

-- CRM-20830
UPDATE `civicrm_option_value`
SET filter = 1
WHERE option_group_id = (SELECT id FROM civicrm_option_group WHERE name = 'activity_status')
AND name = 'Completed';

UPDATE `civicrm_option_value`
SET filter = 2
WHERE option_group_id = (SELECT id FROM civicrm_option_group WHERE name = 'activity_status')
AND name IN ('Cancelled', 'Unreachable', 'Not Required', 'No-show');

-- CRM-20848 : Set non-quick-config price field and their respective price options to active if it's not
UPDATE civicrm_price_field_value cpfv
INNER JOIN civicrm_financial_type cft ON cft.id = cpfv.financial_type_id
INNER JOIN civicrm_price_field pf ON pf.id = cpfv.price_field_id
INNER JOIN civicrm_price_set ps ON ps.id = pf.price_set_id
SET cpfv.is_active = 1
WHERE ps.is_quick_config = 1;

UPDATE civicrm_price_field cpf
LEFT JOIN (SELECT DISTINCT price_field_id AS price_field_id
  FROM civicrm_price_field_value
  WHERE is_active = 1) AS price_field
ON price_field.price_field_id = cpf.id
LEFT JOIN civicrm_price_set ps ON ps.id = cpf.price_set_id
SET cpf.is_active = 1
WHERE ps.is_quick_config = 1 AND cpf.is_active = 0;
