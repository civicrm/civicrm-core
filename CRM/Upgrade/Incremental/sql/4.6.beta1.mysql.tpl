{* file to handle db changes in 4.6.beta1 during upgrade *}
-- Insert menu item for Date Preferences at "Administer > Customize Data and Screens" after Search Preferences
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
