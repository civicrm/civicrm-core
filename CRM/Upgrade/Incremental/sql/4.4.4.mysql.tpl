{* file to handle db changes in 4.4.4 during upgrade *}

{* update comment for civicrm_report_instance.grouprole *}
ALTER TABLE civicrm_report_instance MODIFY grouprole varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'role required to be able to run this instance';

{* CRM-14117 *}
UPDATE civicrm_navigation SET url = 'http://civicrm.org/what/whatiscivicrm' WHERE name = 'About';

-- CRM-13968
SELECT @inprogressstatus := value FROM civicrm_option_value cov 
LEFT JOIN civicrm_option_group cg ON cov.option_group_id = cg.id
WHERE cg.name = 'contribution_status'  AND cov.name = 'In Progress';

SELECT @financialstatus := value FROM civicrm_option_value cov 
LEFT JOIN civicrm_option_group cg ON cov.option_group_id = cg.id
WHERE cg.name = 'financial_item_status'  AND cov.name = 'Unpaid';

SELECT @accountrecievable := id FROM `civicrm_financial_account` WHERE `name` LIKE 'Accounts Receivable';

UPDATE civicrm_financial_trxn cft
LEFT JOIN civicrm_entity_financial_trxn ceft ON ceft.financial_trxn_id = cft.id
LEFT JOIN civicrm_entity_financial_trxn ceft_financial_item ON ceft_financial_item.financial_trxn_id = cft.id
LEFT JOIN civicrm_financial_item cfi ON cfi.id = ceft_financial_item.entity_id
SET to_financial_account_id = @accountrecievable, cfi.status_id = @financialstatus
WHERE ceft.entity_table = 'civicrm_contribution' AND ceft_financial_item.entity_table = 'civicrm_financial_item' AND cft.status_id = @inprogressstatus AND cfi.status_id  IS NULL;

{* CRM-14167 *}
ALTER TABLE civicrm_activity_contact ADD INDEX index_record_type ( activity_id, record_type_id );