{* file to handle db changes in 5.43.alpha1 during upgrade *}
UPDATE civicrm_msg_template SET is_reserved = 0 WHERE is_reserved IS NULL;
ALTER TABLE civicrm_msg_template
MODIFY COLUMN `is_reserved` tinyint(4) DEFAULT 0 COMMENT 'is this the reserved message template which we ship for the workflow referenced by workflow_id?';
