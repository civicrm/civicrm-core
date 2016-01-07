{* file to handle db changes in 4.6.12 during upgrade *}

-- CRM-16173, CRM-16831
SELECT @parent_id := id from `civicrm_navigation` where name = 'System Settings' AND domain_id = {$domainID};
SELECT @add_weight_id := weight from `civicrm_navigation` where `name` = 'Manage Extensions' and `parent_id` = @parent_id;
UPDATE `civicrm_navigation`
SET `weight` = `weight`+1
WHERE `parent_id` = @parent_id
AND `weight` > @add_weight_id;
INSERT INTO `civicrm_navigation`
( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
( {$domainID}, 'civicrm/a/#/cxn', '{ts escape="sql" skip="true"}Connections{/ts}', 'Connections', 'administer CiviCRM', '', @parent_id , '1', NULL, @add_weight_id + 1 );
