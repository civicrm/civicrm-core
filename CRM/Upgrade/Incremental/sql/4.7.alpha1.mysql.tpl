{* file to handle db changes in 4.7.alpha1 during upgrade *}

-- CRM-16354
SELECT @option_group_id_wysiwyg := max(id) from civicrm_option_group where name = 'wysiwyg_editor';

UPDATE civicrm_option_group SET name = 'Textarea', {localize field='label'}label = 'Textarea'{/localize}
  WHERE value = 1 AND option_group_id = @option_group_id_wysiwyg;

DELETE FROM civicrm_option_value WHERE name IN ('Joomla Default Editor', 'Drupal Default Editor')
  AND option_group_id = @option_group_id_wysiwyg;

UPDATE civicrm_option_group SET is_active = 1, is_reserved = 1 WHERE option_group_id = @option_group_id_wysiwyg;

--CRM-16719
SELECT @option_group_id_report := max(id) from civicrm_option_group where name = 'report_template';

UPDATE civicrm_option_value SET {localize field="label"}label = 'Activity Details Report'{/localize}
  WHERE value = 'activity' AND option_group_id = @option_group_id_report;

UPDATE civicrm_option_value SET {localize field="label"}label = 'Activity Summary Report'{/localize}
  WHERE value = 'activitySummary' AND option_group_id = @option_group_id_report;

-- Added is_payment flag 
ALTER TABLE civicrm_financial_trxn ADD COLUMN is_payment tinyint(4) DEFAULT '0' COMMENT 'Is this entry either a payment or a reversal of a payment?';
