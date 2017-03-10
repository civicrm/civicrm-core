{* file to handle db changes in 4.7.13 during upgrade *}

-- CRM-19427
ALTER TABLE  `civicrm_price_field_value` CHANGE  `deductible_amount`  `non_deductible_amount` DECIMAL( 20, 2 ) NOT NULL DEFAULT  '0.00' COMMENT 'Portion of total amount which is NOT tax deductible.';

ALTER TABLE  `civicrm_line_item` CHANGE  `deductible_amount`  `non_deductible_amount` DECIMAL( 20, 2 ) NOT NULL DEFAULT  '0.00' COMMENT 'Portion of total amount which is NOT tax deductible.';

-- CRM-15371 Manage tags with new *manage tags* permission (used to need *administer CiviCRM* permission)
UPDATE civicrm_navigation SET
  `url` = 'civicrm/tag?reset=1',
  `permission` = 'manage tags'
WHERE `name` = 'Manage Tags (Categories)';

UPDATE civicrm_navigation SET
  `url` = 'civicrm/tag?reset=1&action=add',
  `permission` = 'manage tags'
WHERE `name` = 'New Tag';

UPDATE civicrm_navigation SET
  `url` = 'civicrm/tag?reset=1'
WHERE `name` = 'Tags (Categories)';

-- CRM-16352: Add language filter support for mass mailing
ALTER TABLE civicrm_mailing ADD COLUMN `language` varchar(5) DEFAULT NULL COMMENT 'Language of the content of the mailing. Useful for tokens.';
