{* file to handle db changes in 5.43.alpha1 during upgrade *}
UPDATE civicrm_msg_template SET is_reserved = 0 WHERE is_reserved IS NULL;
ALTER TABLE civicrm_msg_template
MODIFY COLUMN `is_reserved` tinyint(4) DEFAULT 0 COMMENT 'is this the reserved message template which we ship for the workflow referenced by workflow_id?';

{* https://github.com/civicrm/civicrm-core/pull/21472 *}
ALTER TABLE civicrm_contribution_recur MODIFY COLUMN modified_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last updated date for this record. mostly the last time a payment was received';
