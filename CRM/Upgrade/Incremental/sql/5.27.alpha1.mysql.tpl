{* file to handle db changes in 5.27.alpha1 during upgrade *}

UPDATE civicrm_custom_field SET serialize = 1, html_type = REPLACE(html_type, 'Multi-', '')
WHERE html_type LIKE 'Multi-%' OR html_type = 'CheckBox';

ALTER TABLE `civicrm_contribution_recur` CHANGE `amount` `amount` DECIMAL( 20,2 ) COMMENT 'Amount to be collected (including any sales tax) by payment processor each recurrence.';
