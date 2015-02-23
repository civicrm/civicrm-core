{* file to handle db changes in 4.6.beta1 during upgrade *}
-- See https://issues.civicrm.org/jira/browse/CRM-15361
UPDATE civicrm_mailing SET location_type_id = NULL WHERE location_type_id = 0;
ALTER TABLE civicrm_mailing ADD CONSTRAINT FK_civicrm_mailing_location_type_id FOREIGN KEY FK_civicrm_mailing_location_type_id(`location_type_id`) REFERENCES `civicrm_location_type`(`id`) ON DELETE SET NULL;

SELECT @parent_id := id from `civicrm_navigation` where name = 'Customize Data and Screens' AND domain_id = {$domainID};
SELECT @add_weight_id := weight from `civicrm_navigation` where `name` = 'Search Preferences' and `parent_id` = @parent_id;
UPDATE `civicrm_navigation`
SET `weight` = `weight`+1
WHERE `parent_id` = @parent_id
AND `weight` > @add_weight_id;
INSERT INTO `civicrm_navigation`
( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
( {$domainID}, 'civicrm/admin/setting/preferences/date?reset=1', '{ts escape="sql" skip="true"}Date Preferences{/ts}', 'Date Preferences', 'administer CiviCRM', '', @parent_id , '1', NULL, @add_weight_id + 1 );
