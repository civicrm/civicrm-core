{* file to handle db changes in 4.7.22 during upgrade *}

{include file='../CRM/Upgrade/4.7.22.msg_template/civicrm_msg_template.tpl'}

-- CRM-20387
UPDATE `civicrm_contribution` SET `invoice_number` = `invoice_id` WHERE `invoice_id` LIKE CONCAT('%', `id`);
