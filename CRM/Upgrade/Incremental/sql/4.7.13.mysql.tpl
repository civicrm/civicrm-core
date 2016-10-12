{* file to handle db changes in 4.7.13 during upgrade *}

-- CRM-19427
ALTER TABLE  `civicrm_price_field_value` CHANGE  `deductible_amount`  `non_deductible_amount` DECIMAL( 20, 2 ) NOT NULL DEFAULT  '0.00' COMMENT 'Portion of total amount which is NOT tax deductible.';

ALTER TABLE  `civicrm_line_item` CHANGE  `deductible_amount`  `non_deductible_amount` DECIMAL( 20, 2 ) NOT NULL DEFAULT  '0.00' COMMENT 'Portion of total amount which is NOT tax deductible.';
