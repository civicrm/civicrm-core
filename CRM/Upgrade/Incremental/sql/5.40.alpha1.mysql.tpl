{* file to handle db changes in 5.40.alpha1 during upgrade *}

SELECT @option_group_id_tuf := max(id) from civicrm_option_group where name = 'tag_used_for';

UPDATE civicrm_option_value SET name = 'Contact'
  WHERE value = 'civicrm_contact' AND option_group_id = @option_group_id_tuf;
UPDATE civicrm_option_value SET name = 'Activity'
  WHERE value = 'civicrm_activity' AND option_group_id = @option_group_id_tuf;
UPDATE civicrm_option_value SET name = 'Case'
  WHERE value = 'civicrm_case' AND option_group_id = @option_group_id_tuf;
UPDATE civicrm_option_value SET name = 'File'
  WHERE value = 'civicrm_file' AND option_group_id = @option_group_id_tuf;

ALTER TABLE civicrm_mailing
MODIFY COLUMN `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date and time this mailing was created.';
