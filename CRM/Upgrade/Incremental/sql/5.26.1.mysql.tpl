{* file to handle db changes in 5.27.alpha1 during upgrade *}

-- dev/core/-/issues/1794
ALTER TABLE `civicrm_custom_group` CHANGE `collapse_adv_display` `collapse_adv_display` TINYINT(4) UNSIGNED NULL DEFAULT '0' COMMENT 'Will this group be in collapsed or expanded mode on advanced search display ?';
ALTER TABLE `civicrm_custom_group` CHANGE `collapse_display` `collapse_display` TINYINT(4) UNSIGNED NULL DEFAULT '0' COMMENT 'Will this group be in collapsed or expanded mode on initial display ?';
