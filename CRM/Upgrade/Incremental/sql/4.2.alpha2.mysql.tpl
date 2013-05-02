{include file='../CRM/Upgrade/4.2.alpha2.msg_template/civicrm_msg_template.tpl'}

-- CRM-10326
DELETE FROM `civicrm_price_set_entity` WHERE entity_table = "civicrm_contribution" or entity_table = "civicrm_participant";

-- When deleting saved searches, null-out references from groups
ALTER TABLE civicrm_group ADD CONSTRAINT `FK_civicrm_group_saved_search_id` 
  FOREIGN KEY (`saved_search_id`) REFERENCES `civicrm_saved_search` (`id`) 
  ON DELETE SET NULL;
