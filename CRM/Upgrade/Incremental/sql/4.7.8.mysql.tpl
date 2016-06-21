{* file to handle db changes in 4.7.8 during upgrade *}

-- CRM-18983 - Do not choke on excess column length when changing image_url column.
DROP INDEX `index_image_url` ON `civicrm_contact`;
CREATE INDEX `index_image_url` ON `civicrm_contact` ( image_URL(128) ) ;
-- CRM-17967 - Allow contact image file name length during upload up to 512 characters long.
ALTER TABLE `civicrm_contact` CHANGE `image_URL` `image_URL` VARCHAR(512) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'optional URL for preferred image (photo, logo, etc.) to display for this contact.';

-- CRM-18516 Convert the date fields relating to group caching and acl caching timestamp
ALTER TABLE civicrm_group CHANGE cache_date cache_date timestamp NULL DEFAULT NULL , CHANGE refresh_date refresh_date timestamp NULL DEFAULT NULL;
ALTER TABLE civicrm_acl_cache CHANGE modified_date modified_date timestamp NULL DEFAULT NULL;

-- CRM-18537
DELETE FROM civicrm_state_province WHERE name = 'Fernando de Noronha';

-- CRM-17118 extend civicrm_address postal_code to accept full data strings from paypal etc.
ALTER TABLE civicrm_address CHANGE `postal_code` `postal_code` varchar(64) ;
