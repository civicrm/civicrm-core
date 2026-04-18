{* file to handle db changes in 6.15.alpha1 during upgrade *}

UPDATE `civicrm_msg_template` t1 INNER JOIN `civicrm_msg_template` t2 SET t1.`msg_text` = ''
WHERE (t1.`is_reserved` = 0) 
    AND (t2.`is_reserved` = 1)
    AND (t1.`workflow_id` = t2.`workflow_id`)
    AND (t1.`msg_text` = t2.`msg_text`)
    AND (t1.`msg_text` <> '');
UPDATE `civicrm_msg_template` SET `msg_text` = '' WHERE (`is_reserved` = 1) AND (`msg_text` <> '');
