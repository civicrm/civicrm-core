{* file to handle db changes in 4.5.beta3 during upgrade *}

-- CRM-14918
UPDATE `civicrm_line_item` cl
INNER JOIN civicrm_membership_payment cmp ON cmp.contribution_id = cl.entity_id
SET cl.entity_table = 'civicrm_membership',
 cl.entity_id = cmp.membership_id
WHERE cl.entity_table = 'civicrm_contribution';
