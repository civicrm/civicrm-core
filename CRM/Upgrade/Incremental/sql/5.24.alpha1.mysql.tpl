{* file to handle db changes in 5.24.alpha1 during upgrade *}

{* #16338 Convert civicrm_note.modified_date to timestamp *}
ALTER TABLE civicrm_note MODIFY COLUMN modified_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When was this note last modified/edited';
