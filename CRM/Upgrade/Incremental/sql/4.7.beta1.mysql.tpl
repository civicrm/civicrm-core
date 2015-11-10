{* file to handle db changes in 4.7.beta1 during upgrade *}

SELECT @parent_id := id from `civicrm_navigation` where name = 'Administration Console' AND domain_id = {$domainID};
INSERT INTO civicrm_navigation
( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
( {$domainID}, 'civicrm/a/#/status', '{ts escape="sql" skip="true"}System Status{/ts}', 'System Status', 'administer CiviCRM', '', @parent_id, '1', NULL, 0 );

UPDATE civicrm_contact SET is_deceased = 0 WHERE is_deceased IS NULL;

-- CRM-17503 PayPal Express processor type can support recurring payments
UPDATE civicrm_payment_processor_type pp
LEFT JOIN civicrm_payment_processor p ON p.payment_processor_type_id = pp.id
SET pp.is_recur = 1, p.is_recur = 1
WHERE pp.name='PayPal_Express';