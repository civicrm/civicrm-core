{* file to handle db changes in 5.27.alpha1 during upgrade *}

ALTER TABLE `civicrm_contribution_recur` CHANGE `amount` `amount` DECIMAL( 20,2 ) COMMENT 'Amount to be collected (including any sales tax) by payment processor each recurrence.';
