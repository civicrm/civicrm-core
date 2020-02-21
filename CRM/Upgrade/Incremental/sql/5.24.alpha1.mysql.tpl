{* file to handle db changes in 5.24.alpha1 during upgrade *}

{* Relabel CKEditor -> CKEditor4 to disambiguate v4 and v5 *}
UPDATE `civicrm_option_value`
SET {localize field="label"} `label` = 'CKEditor4' {/localize}
WHERE name = 'CKEditor' AND option_group_id = (SELECT id FROM `civicrm_option_group` WHERE name = 'wysiwyg_editor');

{* #16338 Convert civicrm_note.modified_date to timestamp *}
ALTER TABLE civicrm_note MODIFY COLUMN modified_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When was this note last modified/edited';
