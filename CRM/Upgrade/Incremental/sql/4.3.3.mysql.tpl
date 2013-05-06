-- CRM-12501 
ALTER TABLE civicrm_financial_account CHANGE `tax_rate` `tax_rate` DECIMAL( 10, 8 ) NULL DEFAULT NULL COMMENT 'The percentage of the total_amount that is due for this tax.';
