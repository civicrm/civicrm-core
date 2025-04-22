-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC. All rights reserved.                        |
-- |                                                                    |
-- | This work is published under the GNU AGPLv3 license with some      |
-- | permitted exceptions and without any warranty. For full license    |
-- | and copyright information, see https://civicrm.org/licensing       |
-- +--------------------------------------------------------------------+
--
-- Generated from {$smarty.template}
-- {$generated}
--
-- This file provides template to civicrm_data.mysql. Inserts all base data needed for a new CiviCRM DB

SET @domainName := 'Default Domain Name';
SET @defaultOrganization := 'Default Organization';

-- Add components to system wide registry
-- We're doing it early to avoid constraint errors.
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviEvent'     , 'CRM_Event' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviContribute', 'CRM_Contribute' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviMember'    , 'CRM_Member' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviMail'      , 'CRM_Mailing' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviGrant'     , 'CRM_Grant' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviPledge'    , 'CRM_Pledge' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviCase'      , 'CRM_Case' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviReport'    , 'CRM_Report' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviCampaign'  , 'CRM_Campaign' );

-- CiviGrant has migrated to an extension, but instead of removing the above insert,
-- go ahead and insert it, then delete. This is because too much legacy code has hard-coded
-- references to component ID, so it's better to keep the auto-increment values stable.
DELETE FROM civicrm_component WHERE name = 'CiviGrant';

-- Create organization contact
INSERT INTO civicrm_contact( `contact_type`, `sort_name`, `display_name`, `legal_name`, `organization_name`)
VALUES ('Organization', @defaultOrganization, @defaultOrganization, @defaultOrganization, @defaultOrganization);
SET @contactID := LAST_INSERT_ID();

INSERT INTO civicrm_email (contact_id, location_type_id, email, is_primary, is_billing, on_hold, hold_date, reset_date)
VALUES
(@contactID, 1, 'fixme.domainemail@example.org', 1, 0, 0, NULL, NULL);

INSERT INTO civicrm_domain (name, version, contact_id) VALUES (@domainName, '2.2', @contactID);
SELECT @domainID := id FROM civicrm_domain where name = 'Default Domain Name';

{crmSqlData file="sql/civicrm_data/civicrm_location_type.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_relationship_type.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_tag.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_mailing_component.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_financial_type.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_site_email_address.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_option_group/*.sqldata.php" exclude=';(encounter_medium|soft_credit_type|recent_items_providers).sqldata.php$;'}

-- CRM-6138
{include file='languages.tpl'}

{crmSqlData file="sql/civicrm_data/civicrm_option_group/encounter_medium.sqldata.php"}

{crmSqlData file="sql/civicrm_data/civicrm_membership_status.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_preferences_date.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_payment_processor_type.sqldata.php"}

{crmSqlData file="sql/civicrm_data/civicrm_dedupe_rule/IndividualSupervised.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_dedupe_rule/OrganizationSupervised.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_dedupe_rule/HouseholdSupervised.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_dedupe_rule/IndividualUnsupervised.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_dedupe_rule/OrganizationUnsupervised.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_dedupe_rule/HouseholdUnsupervised.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_dedupe_rule/IndividualGeneral.sqldata.php"}

{crmSqlData file="sql/civicrm_data/civicrm_county.sqldata.php"}

-- Bounce classification patterns
{crmSqlData file="sql/civicrm_data/civicrm_mailing_bounce_type/AOL.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_mailing_bounce_type/Away.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_mailing_bounce_type/Dns.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_mailing_bounce_type/Host.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_mailing_bounce_type/Inactive.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_mailing_bounce_type/Invalid.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_mailing_bounce_type/Loop.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_mailing_bounce_type/Quota.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_mailing_bounce_type/Relay.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_mailing_bounce_type/Spam.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_mailing_bounce_type/Syntax.sqldata.php"}

{crmSqlData file="sql/civicrm_data/civicrm_uf_group.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_uf_join.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_uf_field.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_participant_status_type.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_contact_type.sqldata.php"}

{include file='civicrm_msg_template.tpl'}

{crmSqlData file="sql/civicrm_data/civicrm_job.sqldata.php"}

-- financial accounts
SELECT @option_group_id_fat            := max(id) from civicrm_option_group where name = 'financial_account_type';
SELECT @opval := value FROM civicrm_option_value WHERE name = 'Revenue' and option_group_id = @option_group_id_fat;
SELECT @opexp := value FROM civicrm_option_value WHERE name = 'Expenses' and option_group_id = @option_group_id_fat;
SELECT @opAsset := value FROM civicrm_option_value WHERE name = 'Asset' and option_group_id = @option_group_id_fat;
SELECT @opLiability := value FROM civicrm_option_value WHERE name = 'Liability' and option_group_id = @option_group_id_fat;
SELECT @opCost := value FROM civicrm_option_value WHERE name = 'Cost of Sales' and option_group_id = @option_group_id_fat;
{crmSqlData file="sql/civicrm_data/civicrm_financial_account.sqldata.php"}

SELECT @option_group_id_arel           := max(id) from civicrm_option_group where name = 'account_relationship';
SELECT @option_value_rel_id  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Income Account is';
SELECT @option_value_rel_id_exp  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Expense Account is';
SELECT @option_value_rel_id_ar  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Accounts Receivable Account is';
SELECT @option_value_rel_id_as  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Asset Account is';
SELECT @option_value_rel_id_cg  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Cost of Sales Account is';
SELECT @option_value_rel_id_dr  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Deferred Revenue Account is';

SELECT @financial_type_id_dtn          := max(id) FROM civicrm_financial_type WHERE name = 'Donation';
SELECT @financial_type_id_md         := max(id) FROM civicrm_financial_type WHERE name = 'Member Dues';
SELECT @financial_type_id_cc         := max(id) FROM civicrm_financial_type WHERE name = 'Campaign Contribution';
SELECT @financial_type_id_ef         := max(id) FROM civicrm_financial_type WHERE name = 'Event Fee';

SELECT @financial_account_id_dtn       := max(id) FROM civicrm_financial_account WHERE name = 'Donation';
SELECT @financial_account_id_md         := max(id) FROM civicrm_financial_account WHERE name = 'Member Dues';
SELECT @financial_account_id_cc         := max(id) FROM civicrm_financial_account WHERE name = 'Campaign Contribution';
SELECT @financial_account_id_ef         := max(id) FROM civicrm_financial_account WHERE name = 'Event Fee';
SELECT @financial_account_id_bf         := max(id) FROM civicrm_financial_account WHERE name = 'Banking Fees';
SELECT @financial_account_id_ap        := max(id) FROM civicrm_financial_account WHERE name = 'Accounts Receivable';
SELECT @financial_account_id_ar        := max(id) FROM civicrm_financial_account WHERE name = 'Deposit Bank Account';
SELECT @financial_account_id_pp        := max(id) FROM civicrm_financial_account WHERE name = 'Payment Processor Account';
SELECT @financial_account_id_pr        := max(id) FROM civicrm_financial_account WHERE name = 'Premiums';
SELECT @financial_account_id_dref      := max(id) FROM civicrm_financial_account WHERE name = 'Deferred Revenue - Event Fee';
SELECT @financial_account_id_drmd      := max(id) FROM civicrm_financial_account WHERE name = 'Deferred Revenue - Member Dues';

INSERT INTO `civicrm_entity_financial_account`
     ( entity_table, entity_id, account_relationship, financial_account_id )
VALUES
     ( 'civicrm_financial_type', @financial_type_id_dtn, @option_value_rel_id, @financial_account_id_dtn ),
     ( 'civicrm_financial_type', @financial_type_id_dtn, @option_value_rel_id_exp, @financial_account_id_bf ),
     ( 'civicrm_financial_type', @financial_type_id_dtn, @option_value_rel_id_ar, @financial_account_id_ap ),
     ( 'civicrm_financial_type', @financial_type_id_dtn, @option_value_rel_id_cg, @financial_account_id_pr ),
     ( 'civicrm_financial_type', @financial_type_id_md, @option_value_rel_id, @financial_account_id_md ),
     ( 'civicrm_financial_type', @financial_type_id_md, @option_value_rel_id_exp, @financial_account_id_bf ),
     ( 'civicrm_financial_type', @financial_type_id_md, @option_value_rel_id_ar, @financial_account_id_ap ),
     ( 'civicrm_financial_type', @financial_type_id_md, @option_value_rel_id_cg, @financial_account_id_pr ),
     ( 'civicrm_financial_type', @financial_type_id_md, @option_value_rel_id_dr, @financial_account_id_drmd ),
     ( 'civicrm_financial_type', @financial_type_id_cc, @option_value_rel_id, @financial_account_id_cc ),
     ( 'civicrm_financial_type', @financial_type_id_cc, @option_value_rel_id_exp, @financial_account_id_bf ),
     ( 'civicrm_financial_type', @financial_type_id_cc, @option_value_rel_id_ar, @financial_account_id_ap ),
     ( 'civicrm_financial_type', @financial_type_id_cc, @option_value_rel_id_cg, @financial_account_id_pr ),
     ( 'civicrm_financial_type', @financial_type_id_ef, @option_value_rel_id_exp, @financial_account_id_bf ),
     ( 'civicrm_financial_type', @financial_type_id_ef, @option_value_rel_id_ar, @financial_account_id_ap ),
     ( 'civicrm_financial_type', @financial_type_id_ef, @option_value_rel_id, @financial_account_id_ef ),
     ( 'civicrm_financial_type', @financial_type_id_ef, @option_value_rel_id_dr, @financial_account_id_dref ),
     ( 'civicrm_financial_type', @financial_type_id_ef, @option_value_rel_id_cg, @financial_account_id_pr );

-- CRM-11516
INSERT INTO  civicrm_entity_financial_account (entity_table, entity_id, account_relationship, financial_account_id)
SELECT 'civicrm_option_value', cov.id, @option_value_rel_id_as, @financial_account_id_ar  FROM `civicrm_option_group` cog
LEFT JOIN civicrm_option_value cov ON cog.id = cov.option_group_id
WHERE cog.name = 'payment_instrument' AND cov.name NOT IN ('Credit Card', 'Debit Card');

SELECT @option_group_id_pi             := max(id) from civicrm_option_group where name = 'payment_instrument';
SELECT @option_value_cc_id  := max(id) FROM `civicrm_option_value` WHERE `option_group_id` = @option_group_id_pi AND `name` = 'Credit Card';
SELECT @option_value_dc_id  := max(id) FROM `civicrm_option_value` WHERE `option_group_id` = @option_group_id_pi AND `name` = 'Debit Card';

INSERT INTO `civicrm_entity_financial_account`
     ( entity_table, entity_id, account_relationship, financial_account_id )
VALUES
     ( 'civicrm_option_value', @option_value_cc_id, @option_value_rel_id_as, @financial_account_id_pp),
     ( 'civicrm_option_value', @option_value_dc_id, @option_value_rel_id_as, @financial_account_id_pp);

-- CRM-9714

INSERT INTO `civicrm_price_set` ( `name`, `title`, `is_active`, `extends`, `is_quick_config`, `financial_type_id`, `is_reserved` )
VALUES ( 'default_contribution_amount', 'Contribution Amount', '1', '2', '1', NULL, 1),
( 'default_membership_type_amount', 'Membership Amount', '1', '3', '1', @financial_type_id_md, 1);

SELECT @setID := max(id) FROM civicrm_price_set WHERE name = 'default_contribution_amount' AND is_quick_config = 1;

INSERT INTO `civicrm_price_field` (`price_set_id`, `name`, `label`, `html_type`,`weight`, `is_display_amounts`, `options_per_line`, `is_active`, `is_required`,`visibility_id` )
VALUES ( @setID, 'contribution_amount', 'Contribution Amount', 'Text', '1', '1', '1', '1', '1', '1' );

SELECT @fieldID := max(id) FROM civicrm_price_field WHERE name = 'contribution_amount' AND price_set_id = @setID;

INSERT INTO `civicrm_price_field_value` (  `price_field_id`, `name`, `label`, `amount`, `weight`, `is_default`, `is_active`, `financial_type_id`)
VALUES ( @fieldID, 'contribution_amount', 'Contribution Amount', '1', '1', '0', '1', 1);

{crmSqlData file="sql/civicrm_data/civicrm_extension.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_option_group/soft_credit_type.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_option_group/recent_items_providers.sqldata.php"}
{crmSqlData file="sql/civicrm_data/civicrm_site_token.sqldata.php"}
