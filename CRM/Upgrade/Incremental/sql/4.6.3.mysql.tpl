{* file to handle db changes in 4.6.3 during upgrade *}

--  CRM-16367: adding the shared payment token table
CREATE TABLE IF NOT EXISTS `civicrm_payment_token` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Payment Token ID',
     `contact_id` int unsigned NOT NULL   COMMENT 'FK to Contact ID for the owner of the token',
     `payment_processor_id` int unsigned NOT NULL   ,
     `token` varchar(255) NOT NULL   COMMENT 'Externally provided token string',
     `created_date` timestamp   DEFAULT CURRENT_TIMESTAMP COMMENT 'Date created',
     `created_id` int unsigned    COMMENT 'Contact ID of token creator',
     `expiry_date` datetime    COMMENT 'Date this token expires',
     `email` varchar(255)    COMMENT 'Email at the time of token creation. Useful for fraud forensics',
     `billing_first_name` varchar(255)    COMMENT 'Billing first name at the time of token creation. Useful for fraud forensics',
     `billing_middle_name` varchar(255)    COMMENT 'Billing middle name at the time of token creation. Useful for fraud forensics',
     `billing_last_name` varchar(255)    COMMENT 'Billing last name at the time of token creation. Useful for fraud forensics',
     `masked_account_number` varchar(255)    COMMENT 'Holds the part of the card number or account details that may be retained or displayed',
     `ip_address` varchar(255)    COMMENT 'IP used when creating the token. Useful for fraud forensics' ,
    PRIMARY KEY ( `id` ),
    CONSTRAINT FK_civicrm_payment_token_contact_id FOREIGN KEY (`contact_id`)
      REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,
    CONSTRAINT FK_civicrm_payment_token_payment_processor_id FOREIGN KEY (`payment_processor_id`)
      REFERENCES `civicrm_payment_processor`(`id`) ON DELETE RESTRICT,
    CONSTRAINT FK_civicrm_payment_token_created_id FOREIGN KEY (`created_id`)
      REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL
)
ENGINE=InnoDB DEFAULT
CHARACTER SET utf8
COLLATE utf8_unicode_ci;

--  CRM-16367: adding a reference to the token table to the recurring contributions table.
ALTER TABLE civicrm_contribution_recur
  ADD COLUMN `payment_token_id` int(10) unsigned DEFAULT NULL COMMENT 'Optionally used to store a link to a payment token used for this recurring contribution.',
  ADD CONSTRAINT `FK_civicrm_contribution_recur_payment_token_id` FOREIGN KEY (`payment_token_id`) REFERENCES `civicrm_payment_token` (`id`) ON DELETE SET NULL;
