{* file to handle db changes in 5.27.alpha1 during upgrade *}

UPDATE civicrm_custom_field SET serialize = 1, html_type = REPLACE(html_type, 'Multi-', '')
WHERE html_type LIKE 'Multi-%' OR html_type = 'CheckBox';

ALTER TABLE `civicrm_contribution_recur` CHANGE `amount` `amount` DECIMAL( 20,2 ) COMMENT 'Amount to be collected (including any sales tax) by payment processor each recurrence.';

-- dev/core/-/issues/1794
ALTER TABLE `civicrm_custom_group` CHANGE `collapse_adv_display` `collapse_adv_display` TINYINT(4) UNSIGNED NULL DEFAULT '0' COMMENT 'Will this group be in collapsed or expanded mode on advanced search display ?';
ALTER TABLE `civicrm_custom_group` CHANGE `collapse_display` `collapse_display` TINYINT(4) UNSIGNED NULL DEFAULT '0' COMMENT 'Will this group be in collapsed or expanded mode on initial display ?';
