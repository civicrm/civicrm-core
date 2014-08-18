{* file to handle db changes in 4.5.beta7 during upgrade *}
UPDATE civicrm_contribution SET net_amount = total_amount - fee_amount WHERE net_amount = 0 OR net_amount IS NULL; 
