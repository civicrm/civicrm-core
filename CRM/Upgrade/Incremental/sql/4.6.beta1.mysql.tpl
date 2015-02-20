{* file to handle db changes in 4.6.beta1 during upgrade *}
-- See https://issues.civicrm.org/jira/browse/CRM-15361
ALTER TABLE civicrm_mailing ADD CONSTRAINT FK_civicrm_mailing_location_type_id FOREIGN KEY FK_civicrm_mailing_location_type_id(`location_type_id`) REFERENCES `civicrm_location_type`(`id`) ON DELETE SET NULL;

