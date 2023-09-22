{* file to handle db changes in 5.66.alpha1 during upgrade *}

{* Ensure action_schedule.name has a unique value *}
UPDATE `civicrm_action_schedule` SET name = COALESCE(CONCAT('repeat_', used_for, '_', entity_value), CONCAT('schedule_', id)) WHERE name IS NULL OR name = '';
UPDATE `civicrm_action_schedule` a1, `civicrm_action_schedule` a2
SET a2.name = CONCAT(a2.name, '_', a2.id)
WHERE a2.name = a1.name AND a2.id > a1.id;

{* Set default value for Discount.entity_table *}
UPDATE `civicrm_discount` SET `entity_table` = 'civicrm_event' WHERE `entity_table` IS NULL;

UPDATE civicrm_contribution SET tax_amount = 0 WHERE tax_amount IS NULL;
UPDATE civicrm_line_item SET tax_amount = 0 WHERE tax_amount IS NULL;
