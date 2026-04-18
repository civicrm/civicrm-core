{* file to handle db changes in 6.14.alpha1 during upgrade *}

-- Should never come up but in the php file we change the column to not null, so this will avoid a crash just in case.
UPDATE civicrm_word_replacement SET find_word = '' WHERE find_word IS NULL;
UPDATE civicrm_word_replacement SET replace_word = '' WHERE replace_word IS NULL;

-- Clean up resdiaul plan-text message templates
UPDATE `civicrm_msg_template` t1 INNER JOIN `civicrm_msg_template` t2 SET t1.`msg_text` = ''
WHERE (t1.`is_reserved` = 0) 
    AND (t2.`is_reserved` = 1)
    AND (t1.`workflow_id` = t2.`workflow_id`)
    AND (t1.`msg_text` = t2.`msg_text`)
    AND (t1.`msg_text` <> '');
UPDATE `civicrm_msg_template` SET `msg_text` = '' WHERE (`is_reserved` = 1) AND (`msg_text` <> '');
