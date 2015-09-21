{* file to handle db changes in 4.6.9 during upgrade *}

-- CRM-17112 - Add Missing countries Saint Barthélemy and Saint Martin
INSERT INTO civicrm_country (name,iso_code,region_id,is_province_abbreviated) VALUES("Saint Barthélemy", "BL", "2", "0");
INSERT INTO civicrm_country (name,iso_code,region_id,is_province_abbreviated) VALUES("Saint Martin (French part)", "MF", "2", "0");

-- CRM-17039 - Add credit note for cancelled payments
{include file='../CRM/Upgrade/4.6.9.msg_template/civicrm_msg_template.tpl'}

-- CRM-17258 - Add created id, owner_id to report instances.
ALTER TABLE civicrm_report_instance
ADD COLUMN `created_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to contact table.',
ADD COLUMN `owner_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to contact table.',
ADD CONSTRAINT `FK_civicrm_report_instance_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `FK_civicrm_report_instance_owner_id` FOREIGN KEY (`owner_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL;
