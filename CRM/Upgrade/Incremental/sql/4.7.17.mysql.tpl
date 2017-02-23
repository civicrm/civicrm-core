{* file to handle db changes in 4.7.17 during upgrade *}

-- CRM-19943
UPDATE civicrm_navigation SET url = 'civicrm/tag' WHERE url = 'civicrm/tag?reset=1';
UPDATE civicrm_navigation SET url = REPLACE(url, 'civicrm/tag', 'civicrm/tag/edit') WHERE url LIKE 'civicrm/tag?%';

-- CRM-19815, CRM-19830 update references to check_number to reflect unique name
UPDATE civicrm_uf_field SET field_name = 'contribution_check_number' WHERE field_name = 'check_number';
UPDATE civicrm_mapping_field SET name = 'contribution_check_number' WHERE name = 'check_number';

-- CRM-20158
ALTER TABLE `civicrm_financial_trxn`
  ADD card_type INT( 10 ) UNSIGNED NULL DEFAULT NULL COMMENT 'FK to accept_creditcard option group values' AFTER payment_instrument_id,
  ADD pan_truncation INT UNSIGNED NULL COMMENT 'Last 4 digits of credit card.' AFTER check_number;
