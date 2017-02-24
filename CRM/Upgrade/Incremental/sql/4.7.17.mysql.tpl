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

-- CRM-19934
CREATE TABLE `civicrm_acl_contacts` (
    `domain_id` int unsigned NOT NULL   COMMENT 'Which Domain is this match entry for',
    `user_id` int unsigned NOT NULL   COMMENT 'FK to civicrm_contact',
    `contact_id` int unsigned NOT NULL   COMMENT 'FK to civicrm_contact',
    `operation_type` int unsigned NOT NULL   COMMENT 'What operation does this user have permission on?',
    PRIMARY KEY (`domain_id`,`user_id`,`contact_id`,`operation_type`),
    CONSTRAINT FK_civicrm_acl_contacts_user_id FOREIGN KEY (`user_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,
    CONSTRAINT FK_civicrm_acl_contacts_contact_id FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;

CREATE TABLE `civicrm_acl_contacts_validity` (
    `domain_id` int unsigned NOT NULL   COMMENT 'Which Domain is this match entry for',
    `user_id` int unsigned NOT NULL   COMMENT 'FK to civicrm_contact',
    `operation_type` int unsigned NOT NULL   COMMENT 'What operation does this user have permission on?',
    `modified_date` timestamp NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When was this record for the last modified',
    PRIMARY KEY (`domain_id`,`user_id`,`operation_type`),
    CONSTRAINT FK_civicrm_acl_contacts_validity_user_id FOREIGN KEY (`user_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;

DROP TABLE `civicrm_acl_contact_cache`;
