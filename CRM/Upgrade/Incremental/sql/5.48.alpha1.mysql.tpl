{* file to handle db changes in 5.48.alpha1 during upgrade *}

{* https://github.com/civicrm/civicrm-core/pull/21539 Deprecate civicrm_contribution_recur.trxn_id *}
ALTER TABLE `civicrm_contribution_recur` MODIFY `processor_id` varchar(255) DEFAULT NULL COMMENT 'May store an identifier used to link this recurring contribution record to a third party payment processor\'s system';
ALTER TABLE `civicrm_contribution_recur` MODIFY `trxn_id` varchar(255) DEFAULT NULL COMMENT 'unique transaction id (deprecated - use processor_id)';
