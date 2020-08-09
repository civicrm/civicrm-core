{* file to handle db changes in 5.29.beta1 during upgrade *}
-- It's really unlikely to be null, but if so just make it something unique.
UPDATE civicrm_price_field_value SET `name` = CONCAT('name', LEFT(SHA1(id), 12)) WHERE `name` IS NULL;
ALTER TABLE civicrm_price_field_value CHANGE `name` `name` varchar(255) NOT NULL COMMENT 'Price field option name';
