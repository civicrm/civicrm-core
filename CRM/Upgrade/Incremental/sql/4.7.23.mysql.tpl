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

-- CRM-12167
ALTER TABLE `civicrm_price_field_value`
ADD COLUMN `visibility_id` int(10);
