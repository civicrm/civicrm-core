{* file to handle db changes in 4.6.3 during upgrade *}
-- CRM-16307 fix CRM-15578 typo - Require access CiviMail permission for A/B Testing feature
UPDATE civicrm_navigation
SET permission = 'access CiviMail', permission_operator = ''
WHERE name = 'New A/B Test' OR name = 'Manage A/B Tests';

--CRM-16320
{include file='../CRM/Upgrade/4.6.3.msg_template/civicrm_msg_template.tpl'}

-- CRM-16452 Missing administrative divisions for Georgia
SELECT @country_id := id from civicrm_country where name = 'Georgia' AND iso_code = 'GE';
INSERT INTO civicrm_state_province (country_id, abbreviation, name)
  VALUES
    (@country_id, "AB", "Abkhazia"),
    (@country_id, "AJ", "Adjara"),
    (@country_id, "TB", "Tbilisi"),
    (@country_id, "GU", "Guria"),
    (@country_id, "IM", "Imereti"),
    (@country_id, "KA", "Kakheti"),
    (@country_id, "KK", "Kvemo Kartli"),
    (@country_id, "MM", "Mtskheta-Mtianeti"),
    (@country_id, "RL", "Racha-Lechkhumi and Kvemo Svaneti"),
    (@country_id, "SZ", "Samegrelo-Zemo Svaneti"),
    (@country_id, "SJ", "Samtskhe-Javakheti"),
    (@country_id, "SK", "Shida Kartli");

--CRM-16391 and CRM-16392
UPDATE civicrm_uf_field
SET {localize field="label"}label = '{ts escape="sql"}Financial Type{/ts}'{/localize}
WHERE field_type = 'Contribution' AND field_name='financial_type';

UPDATE civicrm_uf_field
SET {localize field="label"}label = '{ts escape="sql"}Membership Type{/ts}'{/localize}
WHERE field_type = 'Membership' AND field_name='membership_type';

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

--CRM-16480: set total_amount and financial_type fields 'is_required' to null
SELECT @uf_group_id_contribution := max(id) from civicrm_uf_group where name = 'contribution_batch_entry';
SELECT @uf_group_id_membership := max(id) from civicrm_uf_group where name = 'membership_batch_entry';

UPDATE civicrm_uf_field
SET is_required = 0 WHERE uf_group_id IN (@uf_group_id_contribution, @uf_group_id_membership) AND field_name IN ('financial_type', 'total_amount');
