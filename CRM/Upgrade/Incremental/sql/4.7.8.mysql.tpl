{* file to handle db changes in 4.7.8 during upgrade *}

#CRM-17967 - Allow conact image file name length during upload up to 255 characters long
ALTER TABLE `civicrm_contact` CHANGE `image_URL` `image_URL` VARCHAR(512) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'optional URL for preferred image (photo, logo, etc.) to display for this contact.';
