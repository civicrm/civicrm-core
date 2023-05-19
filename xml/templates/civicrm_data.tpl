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

-- Sample location types
-- CRM-9120 for legacy reasons we are continuing to translate the 'name', but this
-- field is used mainly as an ID, and display_name will be shown to the user, but
-- we have not yet finished modifying all places where the 'name' is shown.
INSERT INTO civicrm_location_type( name, display_name, vcard_name, description, is_reserved, is_active, is_default ) VALUES( '{ts escape="sql"}Home{/ts}', '{ts escape="sql"}Home{/ts}', 'HOME', '{ts escape="sql"}Place of residence{/ts}', 0, 1, 1 );
INSERT INTO civicrm_location_type( name, display_name, vcard_name, description, is_reserved, is_active ) VALUES( '{ts escape="sql"}Work{/ts}', '{ts escape="sql"}Work{/ts}', 'WORK', '{ts escape="sql"}Work location{/ts}', 0, 1 );
INSERT INTO civicrm_location_type( name, display_name, vcard_name, description, is_reserved, is_active ) VALUES( '{ts escape="sql"}Main{/ts}', '{ts escape="sql"}Main{/ts}', NULL, '{ts escape="sql"}Main office location{/ts}', 0, 1 );
INSERT INTO civicrm_location_type( name, display_name, vcard_name, description, is_reserved, is_active ) VALUES( '{ts escape="sql"}Other{/ts}', '{ts escape="sql"}Other{/ts}', NULL, '{ts escape="sql"}Other location{/ts}', 0, 1 );
-- the following location must stay with the untranslated Billing name, CRM-2064
INSERT INTO civicrm_location_type( name, display_name, vcard_name, description, is_reserved, is_active ) VALUES( 'Billing',  '{ts escape="sql"}Billing{/ts}', NULL, '{ts escape="sql"}Billing Address location{/ts}', 1, 1 );

-- Sample relationship types
INSERT INTO civicrm_relationship_type( name_a_b,label_a_b, name_b_a,label_b_a, description, contact_type_a, contact_type_b, is_reserved )
    VALUES( 'Child of', '{ts escape="sql"}Child of{/ts}', 'Parent of', '{ts escape="sql"}Parent of{/ts}', '{ts escape="sql"}Parent/child relationship.{/ts}', 'Individual', 'Individual', 0 ),
          ( 'Spouse of', '{ts escape="sql"}Spouse of{/ts}', 'Spouse of', '{ts escape="sql"}Spouse of{/ts}', '{ts escape="sql"}Spousal relationship.{/ts}', 'Individual', 'Individual', 0 ),
          ( 'Partner of', '{ts escape="sql"}Partner of{/ts}', 'Partner of', '{ts escape="sql"}Partner of{/ts}', '{ts escape="sql"}Partner relationship.{/ts}', 'Individual', 'Individual', 0 ),
          ( 'Sibling of', '{ts escape="sql"}Sibling of{/ts}', 'Sibling of', '{ts escape="sql"}Sibling of{/ts}', '{ts escape="sql"}Sibling relationship.{/ts}', 'Individual','Individual', 0 ),
          ( 'Employee of', '{ts escape="sql"}Employee of{/ts}', 'Employer of', '{ts escape="sql"}Employer of{/ts}', '{ts escape="sql"}Employment relationship.{/ts}','Individual','Organization', 1 ),
          ( 'Volunteer for', '{ts escape="sql"}Volunteer for{/ts}', 'Volunteer is', '{ts escape="sql"}Volunteer is{/ts}', '{ts escape="sql"}Volunteer relationship.{/ts}','Individual','Organization', 0 ),
          ( 'Head of Household for', '{ts escape="sql"}Head of Household for{/ts}', 'Head of Household is', '{ts escape="sql"}Head of Household is{/ts}', '{ts escape="sql"}Head of household.{/ts}','Individual','Household', 1 ),
          ( 'Household Member of', '{ts escape="sql"}Household Member of{/ts}', 'Household Member is', '{ts escape="sql"}Household Member is{/ts}', '{ts escape="sql"}Household membership.{/ts}','Individual','Household', 1 );

-- Relationship Types for CiviCase
INSERT INTO civicrm_relationship_type( name_a_b,label_a_b, name_b_a,label_b_a, description, contact_type_a, contact_type_b, is_reserved )
    VALUES( 'Case Coordinator is', 'Case Coordinator is', 'Case Coordinator', 'Case Coordinator', 'Case Coordinator', 'Individual', 'Individual', 0 );
INSERT INTO civicrm_relationship_type( name_a_b,label_a_b, name_b_a,label_b_a, description, contact_type_a, contact_type_b, is_reserved )
    VALUES( 'Supervised by', 'Supervised by', 'Supervisor', 'Supervisor', 'Immediate workplace supervisor', 'Individual', 'Individual', 0 );


-- Sample Tags
INSERT INTO civicrm_tag( name, description, parent_id,used_for )
    VALUES
    ( '{ts escape="sql"}Non-profit{/ts}', '{ts escape="sql"}Any not-for-profit organization.{/ts}', NULL,'civicrm_contact'),
    ( '{ts escape="sql"}Company{/ts}', '{ts escape="sql"}For-profit organization.{/ts}', NULL,'civicrm_contact'),
    ( '{ts escape="sql"}Government Entity{/ts}', '{ts escape="sql"}Any governmental entity.{/ts}', NULL,'civicrm_contact'),
    ( '{ts escape="sql"}Major Donor{/ts}', '{ts escape="sql"}High-value supporter of our organization.{/ts}', NULL,'civicrm_contact'),
    ( '{ts escape="sql"}Volunteer{/ts}', '{ts escape="sql"}Active volunteers.{/ts}', NULL,'civicrm_contact' );

{capture assign=subgroup}{ldelim}subscribe.group{rdelim}{/capture}
{capture assign=suburl}{ldelim}subscribe.url{rdelim}{/capture}
{capture assign=welgroup}{ldelim}welcome.group{rdelim}{/capture}
{capture assign=unsubgroup}{ldelim}unsubscribe.group{rdelim}{/capture}
{capture assign=actresub}{ldelim}action.resubscribe{rdelim}{/capture}
{capture assign=actresuburl}{ldelim}action.resubscribeUrl{rdelim}{/capture}
{capture assign=resubgroup}{ldelim}resubscribe.group{rdelim}{/capture}
{capture assign=actunsub}{ldelim}action.unsubscribe{rdelim}{/capture}
{capture assign=actunsuburl}{ldelim}action.unsubscribeUrl{rdelim}{/capture}
{capture assign=domname}{ldelim}domain.name{rdelim}{/capture}

-- sample CiviCRM mailing components
INSERT INTO civicrm_mailing_component
    (name,component_type,subject,body_html,body_text,is_default,is_active)
VALUES
    ('{ts escape="sql"}Mailing Header{/ts}','Header','{ts escape="sql"}Descriptive Title for this Header{/ts}','{ts escape="sql"}Sample Header for HTML formatted content.{/ts}','{ts escape="sql"}Sample Header for TEXT formatted content.{/ts}',1,1),
    ('{ts escape="sql"}Mailing Footer{/ts}','Footer','{ts escape="sql"}Descriptive Title for this Footer.{/ts}','{ts escape="sql"}Sample Footer for HTML formatted content<br/><a href="{ldelim}action.optOutUrl{rdelim}">Unsubscribe</a>  <br/> {ldelim}domain.address{rdelim}{/ts}','{ts escape="sql"}to unsubscribe: {ldelim}action.optOutUrl{rdelim}
{ldelim}domain.address{rdelim}{/ts}',1,1),
    ('{ts escape="sql"}Subscribe Message{/ts}','Subscribe','{ts escape="sql"}Subscription Confirmation Request{/ts}','{ts escape="sql" 1=$subgroup 2=$suburl}You have a pending subscription to the %1 mailing list. To confirm this subscription, reply to this email or click <a href="%2">here</a>.{/ts}','{ts escape="sql" 1=$subgroup 2=$suburl}You have a pending subscription to the %1 mailing list. To confirm this subscription, reply to this email or click on this link: %2{/ts}',1,1),
    ('{ts escape="sql"}Welcome Message{/ts}','Welcome','{ts escape="sql"}Your Subscription has been Activated{/ts}','{ts escape="sql" 1=$welgroup}Welcome. Your subscription to the %1 mailing list has been activated.{/ts}','{ts escape="sql" 1=$welgroup}Welcome. Your subscription to the %1 mailing list has been activated.{/ts}',1,1),
    ('{ts escape="sql"}Unsubscribe Message{/ts}','Unsubscribe','{ts escape="sql"}Un-subscribe Confirmation{/ts}','{ts escape="sql" 1=$unsubgroup 2=$actresub 3=$actresuburl}You have been un-subscribed from the following groups: %1. You can re-subscribe by mailing %2 or clicking <a href="%3">here</a>.{/ts}','{ts escape="sql" 1=$unsubgroup 2=$actresub 3=$actresuburl}You have been un-subscribed from the following groups: %1. You can re-subscribe by mailing %2 or clicking %3{/ts}',1,1),
    ('{ts escape="sql"}Resubscribe Message{/ts}','Resubscribe','{ts escape="sql"}Re-subscribe Confirmation{/ts}','{ts escape="sql" 1=$resubgroup 2=$actunsub 3=$actunsuburl}You have been re-subscribed to the following groups: %1. You can un-subscribe by mailing %2 or clicking <a href="%3">here</a>.{/ts}','{ts escape="sql" 1=$resubgroup 2=$actunsub 3=$actunsuburl}You have been re-subscribed to the following groups: %1. You can un-subscribe by mailing %2 or clicking %3{/ts}',1,1),
    ('{ts escape="sql"}Opt-out Message{/ts}','OptOut','{ts escape="sql"}Opt-out Confirmation{/ts}','{ts escape="sql" 1=$domname}Your email address has been removed from %1 mailing lists.{/ts}','{ts escape="sql" 1=$domname}Your email address has been removed from %1 mailing lists.{/ts}',1,1),
    ('{ts escape="sql"}Auto-responder{/ts}','Reply','{ts escape="sql"}Please Send Inquiries to Our Contact Email Address{/ts}','{ts escape="sql"}This is an automated reply from an un-attended mailbox. Please send any inquiries to the contact email address listed on our web-site.{/ts}','{ts escape="sql"}This is an automated reply from an un-attended mailbox. Please send any inquiries to the contact email address listed on our web-site.{/ts}',1,1);


-- contribution types
INSERT INTO
   civicrm_financial_type(name, is_reserved, is_active, is_deductible)
VALUES
  ( '{ts escape="sql"}Donation{/ts}'             , 0, 1, 1 ),
  ( '{ts escape="sql"}Member Dues{/ts}'          , 0, 1, 1 ),
  ( '{ts escape="sql"}Campaign Contribution{/ts}', 0, 1, 0 ),
  ( '{ts escape="sql"}Event Fee{/ts}'            , 0, 1, 0 );

-- option groups and values for 'preferred communication methods' , 'activity types', 'gender', etc.

{php}
  $optionGroups = include 'sql/civicrm_data/civicrm_option_group.php';
  $laterGroups = ['encounter_medium', 'soft_credit_type', 'recent_items_providers'];
  foreach ($optionGroups as $groupName => $group) {
    if (!in_array($groupName, $laterGroups)) {
      echo $group->toSQL();
    }
  }
{/php}

SELECT @option_group_id_pi             := max(id) from civicrm_option_group where name = 'payment_instrument';
SELECT @option_group_id_arel           := max(id) from civicrm_option_group where name = 'account_relationship';
SELECT @option_group_id_fat            := max(id) from civicrm_option_group where name = 'financial_account_type';

SELECT @contributeCompId := max(id) FROM civicrm_component where name = 'CiviContribute';
SELECT @eventCompId      := max(id) FROM civicrm_component where name = 'CiviEvent';
SELECT @memberCompId     := max(id) FROM civicrm_component where name = 'CiviMember';
SELECT @pledgeCompId     := max(id) FROM civicrm_component where name = 'CiviPledge';
SELECT @caseCompId       := max(id) FROM civicrm_component where name = 'CiviCase';
SELECT @campaignCompId   := max(id) FROM civicrm_component where name = 'CiviCampaign';
SELECT @mailCompId       := max(id) FROM civicrm_component where name = 'CiviMail';

-- financial accounts
SELECT @opval := value FROM civicrm_option_value WHERE name = 'Revenue' and option_group_id = @option_group_id_fat;
SELECT @opexp := value FROM civicrm_option_value WHERE name = 'Expenses' and option_group_id = @option_group_id_fat;
SELECT @opAsset := value FROM civicrm_option_value WHERE name = 'Asset' and option_group_id = @option_group_id_fat;
SELECT @opLiability := value FROM civicrm_option_value WHERE name = 'Liability' and option_group_id = @option_group_id_fat;
SELECT @opCost := value FROM civicrm_option_value WHERE name = 'Cost of Sales' and option_group_id = @option_group_id_fat;

INSERT INTO
   `civicrm_financial_account` (`name`, `contact_id`, `financial_account_type_id`, `description`, `accounting_code`, `account_type_code`, `is_reserved`, `is_active`, `is_deductible`, `is_default`)
VALUES
  ( '{ts escape="sql"}Donation{/ts}'            , @contactID, @opval, 'Default account for donations', '4200', 'INC', 0, 1, 1, 1 ),
  ( '{ts escape="sql"}Member Dues{/ts}'          , @contactID, @opval, 'Default account for membership sales', '4400', 'INC', 0, 1, 1, 0 ),
  ( '{ts escape="sql"}Campaign Contribution{/ts}', @contactID, @opval, 'Sample account for recording payments to a campaign', '4100', 'INC', 0, 1, 0, 0 ),
  ( '{ts escape="sql"}Event Fee{/ts}'            , @contactID, @opval, 'Default account for event ticket sales', '4300', 'INC', 0, 1, 0, 0 ),
  ( '{ts escape="sql"}Banking Fees{/ts}'         , @contactID, @opexp, 'Payment processor fees and manually recorded banking fees', '5200', 'EXP', 0, 1, 0, 1 ),
  ( '{ts escape="sql"}Deposit Bank Account{/ts}' , @contactID, @opAsset, 'All manually recorded cash and cheques go to this account', '1100', 'BANK', 0, 1, 0, 1 ),
  ( '{ts escape="sql"}Accounts Receivable{/ts}'  , @contactID, @opAsset, 'Amounts to be received later (eg pay later event revenues)', '1200', 'AR', 0, 1, 0, 0 ),
  ( '{ts escape="sql"}Accounts Payable{/ts}'     , @contactID, @opLiability, 'Amounts to be paid out such as grants and refunds', '2200', 'AP', 0, 1, 0, 1 ),
  ( '{ts escape="sql"}Premiums{/ts}'             , @contactID, @opCost, 'Account to record cost of premiums provided to payors', '5100', 'COGS', 0, 1, 0, 1 ),
  ( '{ts escape="sql"}Premiums inventory{/ts}'   , @contactID, @opAsset, 'Account representing value of premiums inventory', '1375', 'OCASSET', 0, 1, 0, 0 ),
  ( '{ts escape="sql"}Discounts{/ts}'            , @contactID, @opval, 'Contra-revenue account for amounts discounted from sales', '4900', 'INC', 0, 1, 0, 0 ),
  ( '{ts escape="sql"}Payment Processor Account{/ts}', @contactID, @opAsset, 'Account to record payments into a payment processor merchant account', '1150', 'BANK', 0, 1, 0, 0),
  ( '{ts escape="sql"}Deferred Revenue - Event Fee{/ts}', @contactID, @opLiability, 'Event revenue to be recognized in future months when the events occur', '2730', 'OCLIAB', 0, 1, 0, 0),
  ( '{ts escape="sql"}Deferred Revenue - Member Dues{/ts}', @contactID, @opLiability, 'Membership revenue to be recognized in future months', '2740', 'OCLIAB', 0, 1, 0, 0
);


-- CRM-6138
{include file='languages.tpl'}

{php}echo $optionGroups['encounter_medium']->toSQL();{/php}

-- sample membership status entries
INSERT INTO
    civicrm_membership_status(name, label, start_event, start_event_adjust_unit, start_event_adjust_interval, end_event, end_event_adjust_unit, end_event_adjust_interval, is_current_member, is_admin, weight, is_default, is_active, is_reserved)
VALUES
    ('New',       '{ts escape="sql"}New{/ts}', 'join_date', null, null,'join_date','month',3, 1, 0, 1, 0, 1, 0),
    ('Current',   '{ts escape="sql"}Current{/ts}', 'start_date', null, null,'end_date', null, null, 1, 0, 2, 1, 1, 0),
    ('Grace',     '{ts escape="sql"}Grace{/ts}', 'end_date', null, null,'end_date','month', 1, 1, 0, 3, 0, 1, 0),
    ('Expired',   '{ts escape="sql"}Expired{/ts}', 'end_date', 'month', 1, null, null, null, 0, 0, 4, 0, 1, 0),
    ('Pending',   '{ts escape="sql"}Pending{/ts}', 'join_date', null, null,'join_date',null,null, 0, 0, 5, 0, 1, 1),
    ('Cancelled', '{ts escape="sql"}Cancelled{/ts}', 'join_date', null, null,'join_date',null,null, 0, 0, 6, 0, 1, 1),
    ('Deceased',  '{ts escape="sql"}Deceased{/ts}', null, null, null, null, null, null, 0, 1, 7, 0, 1, 1);


INSERT INTO `civicrm_preferences_date`
  (name, start, end, date_format, time_format, description)
VALUES
  ( 'activityDate'    ,  20, 10, '',    '',  '{ts escape="sql"}Date for relationships. activities. contributions: receive, receipt, cancel. membership: join, start, renew. case: start, end.{/ts}'         ),
  ( 'activityDateTime',  20, 10, '',     1,  '{ts escape="sql"}Date and time for activity: scheduled. participant: registered.{/ts}'                                                                  ),
  ( 'birth'           , 100,  0, '',    '',  '{ts escape="sql"}Birth and deceased dates. Only year, month and day fields are supported.{/ts}'                                                         ),
  ( 'creditCard'      ,   0, 10, 'M Y', '',  '{ts escape="sql"}Month and year only for credit card expiration.{/ts}'                                                                                  ),
  ( 'custom'          ,  20, 20, '',    '',  '{ts escape="sql"}Uses date range passed in by form field. Can pass in a posix date part parameter. Start and end offsets defined here are ignored.{/ts}'),
  ( 'mailing'         ,   0,  1, '',    '',  '{ts escape="sql"}Date and time. Used for scheduling mailings.{/ts}'                                                                                     ),
  ( 'searchDate'      ,  20, 20, '',    '',  '{ts escape="sql"}Used in search forms.{/ts}'                                                                                      );


-- various processor options
--
-- Table structure for table `civicrm_payment_processor_type`
--

INSERT INTO `civicrm_payment_processor_type`
 (name, title, description, is_active, is_default, user_name_label, password_label, signature_label, subject_label, class_name, url_site_default, url_api_default, url_recur_default, url_button_default, url_site_test_default, url_api_test_default, url_recur_test_default, url_button_test_default, billing_mode, is_recur )
VALUES
 ('PayPal_Standard',    '{ts escape="sql"}PayPal - Website Payments Standard{/ts}', NULL,1,0,'{ts escape="sql"}Merchant Account Email{/ts}',NULL,NULL,NULL,'Payment_PayPalImpl','https://www.paypal.com/',NULL,'https://www.paypal.com/',NULL,'https://www.sandbox.paypal.com/',NULL,'https://www.sandbox.paypal.com/',NULL,4,1),
 ('PayPal',             '{ts escape="sql"}PayPal - Website Payments Pro{/ts}',      NULL,1,0,'{ts escape="sql"}User Name{/ts}','{ts escape="sql"}Password{/ts}','{ts escape="sql"}Signature{/ts}',NULL,'Payment_PayPalImpl','https://www.paypal.com/','https://api-3t.paypal.com/','https://www.paypal.com/','https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif','https://www.sandbox.paypal.com/','https://api-3t.sandbox.paypal.com/','https://www.sandbox.paypal.com/','https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif',3, 1),
 ('PayPal_Express',     '{ts escape="sql"}PayPal - Express{/ts}',       NULL,1,0,'{ts escape="sql"}User Name{/ts}','{ts escape="sql"}Password{/ts}','{ts escape="sql"}Signature{/ts}',NULL,'Payment_PayPalImpl','https://www.paypal.com/','https://api-3t.paypal.com/',NULL,'https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif','https://www.sandbox.paypal.com/','https://api-3t.sandbox.paypal.com/',NULL,'https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif',2, 1),
 ('AuthNet',            '{ts escape="sql"}Authorize.Net{/ts}',          NULL,1,0,'{ts escape="sql"}API Login{/ts}','{ts escape="sql"}Payment Key{/ts}','{ts escape="sql"}MD5 Hash{/ts}',NULL,'Payment_AuthorizeNet','https://secure2.authorize.net/gateway/transact.dll',NULL,'https://api2.authorize.net/xml/v1/request.api',NULL,'https://test.authorize.net/gateway/transact.dll',NULL,'https://apitest.authorize.net/xml/v1/request.api',NULL,1,1),
 ('PayJunction',        '{ts escape="sql"}PayJunction{/ts}',            NULL,0,0,'User Name','Password',NULL,NULL,'Payment_PayJunction','https://payjunction.com/quick_link',NULL,NULL,NULL,'https://www.payjunctionlabs.com/quick_link',NULL,NULL,NULL,1,1),
 ('Dummy',              '{ts escape="sql"}Dummy Payment Processor{/ts}',NULL,1,1,'{ts escape="sql"}User Name{/ts}',NULL,NULL,NULL,'Payment_Dummy',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1),
 ('Realex',             '{ts escape="sql"}Realex Payment{/ts}',         NULL,0,0,'Merchant ID', 'Password', NULL, 'Account', 'Payment_Realex', 'https://epage.payandshop.com/epage.cgi', NULL, NULL, NULL, 'https://epage.payandshop.com/epage-remote.cgi', NULL, NULL, NULL, 1, 0),
 ('FirstData',          '{ts escape="sql"}FirstData (aka linkpoint){/ts}', '{ts escape="sql"}FirstData (aka linkpoint){/ts}', 0, 0, 'Store name', 'certificate path', NULL, NULL, 'Payment_FirstData', 'https://secure.linkpt.net', NULL, NULL, NULL, 'https://staging.linkpt.net', NULL, NULL, NULL, 1, 0);


-- the fuzzy default dedupe rules
-- IndividualSupervised uses hard-coded optimized query (CRM_Dedupe_BAO_QueryBuilder_IndividualSupervised)
INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, used, name, title, is_reserved)
VALUES ('Individual', 20, 'Supervised', 'IndividualSupervised', '{ts escape="sql"}Name and Email (reserved){/ts}', 1);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_contact', 'first_name', 5),
       (@drgid, 'civicrm_contact', 'last_name',  7),
       (@drgid, 'civicrm_email'  , 'email',     10);

INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, used, name, title, is_reserved)
VALUES ('Organization', 10, 'Supervised', 'OrganizationSupervised', '{ts escape="sql"}Name and Email{/ts}', 0);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_contact', 'organization_name', 10),
       (@drgid, 'civicrm_email'  , 'email',             10);

INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, used, name, title, is_reserved)
VALUES ('Household', 10, 'Supervised', 'HouseholdSupervised', '{ts escape="sql"}Name and Email{/ts}', 0);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_contact', 'household_name', 10),
       (@drgid, 'civicrm_email'  , 'email',          10);

-- the strict dedupe rules
-- IndividualUnsupervised uses hard-coded optimized query (CRM_Dedupe_BAO_QueryBuilder_IndividualUnsupervised)
INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, used, name, title, is_reserved)
VALUES ('Individual', 10, 'Unsupervised', 'IndividualUnsupervised', '{ts escape="sql"}Email (reserved){/ts}', 1);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_email', 'email', 10);

INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, used, name, title, is_reserved)
VALUES ('Organization', 10,  'Unsupervised', 'OrganizationUnsupervised', '{ts escape="sql"}Name and Email{/ts}', 0);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_contact', 'organization_name', 10),
       (@drgid, 'civicrm_email'  , 'email',             10);

INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, used, name, title, is_reserved)
VALUES ('Household', 10, 'Unsupervised', 'HouseholdUnsupervised', '{ts escape="sql"}Name and Email{/ts}', 0);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_contact', 'household_name', 10),
       (@drgid, 'civicrm_email'  , 'email',          10);

-- IndividualGeneral uses hard-coded optimized query (CRM_Dedupe_BAO_QueryBuilder_IndividualGeneral)
INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, used, name, title, is_reserved)
VALUES ('Individual', 15, 'General', 'IndividualGeneral', '{ts escape="sql"}Name and Address (reserved){/ts}', 1);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_contact', 'first_name',     '5'),
       (@drgid, 'civicrm_contact', 'last_name',      '5'),
       (@drgid, 'civicrm_address', 'street_address', '5'),
       (@drgid, 'civicrm_contact', 'middle_name',    '1'),
       (@drgid, 'civicrm_contact', 'suffix_id',      '1');

-- Sample counties (state-province and country lists defined in a separate tpl files)
INSERT INTO civicrm_county (name, state_province_id) VALUES ('Alameda', 1004);
INSERT INTO civicrm_county (name, state_province_id) VALUES ('Contra Costa', 1004);
INSERT INTO civicrm_county (name, state_province_id) VALUES ('Marin', 1004);
INSERT INTO civicrm_county (name, state_province_id) VALUES ('San Francisco', 1004);
INSERT INTO civicrm_county (name, state_province_id) VALUES ('San Mateo', 1004);
INSERT INTO civicrm_county (name, state_province_id) VALUES ('Santa Clara', 1004);

-- Bounce classification patterns
INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('AOL', '{ts escape="sql"}AOL Terms of Service complaint{/ts}', 1);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'AOL';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, 'Client TOS Notification');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Away', '{ts escape="sql"}Recipient is on vacation{/ts}', 30);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Away';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, '(be|am)? (out of|away from) (the|my)? (office|computer|town)'),
    (@bounceTypeID, 'i am on vacation');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Dns', '{ts escape="sql"}Unable to resolve recipient domain{/ts}', 3);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Dns';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, 'name(server entry| lookup failure)'),
    (@bounceTypeID, 'no (mail server|matches to nameserver query|dns entries)'),
    (@bounceTypeID, 'reverse dns entry'),
    (@bounceTypeID, 'Host or domain name not found'),
    (@bounceTypeID, 'Unable to resolve MX record for');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Host', '{ts escape="sql"}Unable to deliver to destintation mail server{/ts}', 3);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Host';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, '(unknown|not local) host'),
    (@bounceTypeID, 'all hosts have been failing'),
    (@bounceTypeID, 'allowed rcpthosts'),
    (@bounceTypeID, 'connection (refused|timed out)'),
    (@bounceTypeID, 'not connected'),
    (@bounceTypeID, 'couldn\'t find any host named'),
    (@bounceTypeID, 'error involving remote host'),
    (@bounceTypeID, 'host unknown'),
    (@bounceTypeID, 'invalid host name'),
    (@bounceTypeID, 'isn\'t in my control/locals file'),
    (@bounceTypeID, 'local configuration error'),
    (@bounceTypeID, 'not a gateway'),
    (@bounceTypeID, 'server is (down or unreachable|not responding)'),
    (@bounceTypeID, 'too many connections'),
    (@bounceTypeID, 'unable to connect'),
    (@bounceTypeID, 'lost connection'),
    (@bounceTypeID, 'conversation with [^ ]* timed out while'),
    (@bounceTypeID, 'server requires authentication'),
    (@bounceTypeID, 'authentication (is )?required');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Inactive', '{ts escape="sql"}User account is no longer active{/ts}', 1);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Inactive';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, '(my )?e-?mail( address)? has changed'),
    (@bounceTypeID, 'account (inactive|expired|deactivated)'),
    (@bounceTypeID, 'account is locked'),
    (@bounceTypeID, 'changed \w+( e-?mail)? address'),
    (@bounceTypeID, 'deactivated mailbox'),
    (@bounceTypeID, 'disabled or discontinued'),
    (@bounceTypeID, 'inactive user'),
    (@bounceTypeID, 'is inactive on this domain'),
    (@bounceTypeID, 'mail receiving disabled'),
    (@bounceTypeID, 'mail( ?)address is administrative?ly disabled'),
    (@bounceTypeID, 'mailbox (temporarily disabled|currently suspended)'),
    (@bounceTypeID, 'no longer (accepting mail|on server|in use|with|employed|on staff|works for|using this account)'),
    (@bounceTypeID, 'not accepting (mail|messages)'),
    (@bounceTypeID, 'please use my new e-?mail address'),
    (@bounceTypeID, 'this address no longer accepts mail'),
    (@bounceTypeID, 'user account suspended'),
    (@bounceTypeID, 'account that you tried to reach is disabled'),
    (@bounceTypeID, 'User banned');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Invalid', '{ts escape="sql"}Email address is not valid{/ts}', 1);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Invalid';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, '(user|recipient( name)?) is not recognized'),
    (@bounceTypeID, '554 delivery error'),
    (@bounceTypeID, 'address does not exist'),
    (@bounceTypeID, 'address(es)?( you (entered|specified))? (could|was)( not|n.t)( be)? found'),
    (@bounceTypeID, 'address(ee)? (unknown|invalid)'),
    (@bounceTypeID, 'bad destination'),
    (@bounceTypeID, 'badly formatted address'),
    (@bounceTypeID, 'can\'t open mailbox for'),
    (@bounceTypeID, 'cannot deliver'),
    (@bounceTypeID, 'delivery to the following recipient(s)? failed'),
    (@bounceTypeID, 'destination addresses were unknown'),
    (@bounceTypeID, 'did not reach the following recipient'),
    (@bounceTypeID, 'does not exist'),
    (@bounceTypeID, 'does not like recipient'),
    (@bounceTypeID, 'does not specify a valid notes mail file'),
    (@bounceTypeID, 'illegal alias'),
    (@bounceTypeID, 'invalid (mailbox|(e-?mail )?address|recipient|final delivery)'),
    (@bounceTypeID, 'invalid( or unknown)?( virtual)? user'),
    (@bounceTypeID, '(mail )?delivery (to this user )?is not allowed'),
    (@bounceTypeID, 'mailbox (not found|unavailable|name not allowed)'),
    (@bounceTypeID, 'message could not be forwarded'),
    (@bounceTypeID, 'missing or malformed local(-| )part'),
    (@bounceTypeID, 'no e-?mail address registered'),
    (@bounceTypeID, 'no such (mail drop|mailbox( \\w+)?|(e-?mail )?address|recipient|(local )?user|person)( here)?'),
    (@bounceTypeID, 'no mailbox (here )?by that name'),
    (@bounceTypeID, 'not (listed in|found in directory|known at this site|our customer)'),
    (@bounceTypeID, 'not a valid( (user|mailbox))?'),
    (@bounceTypeID, 'not present in directory entry'),
    (@bounceTypeID, 'recipient (does not exist|(is )?unknown|rejected|denied|not found)'),
    (@bounceTypeID, 'this user doesn\'t have a yahoo.com address'),
    (@bounceTypeID, 'unavailable to take delivery of the message'),
    (@bounceTypeID, 'unavailable mailbox'),
    (@bounceTypeID, 'unknown (local( |-)part|recipient|address error)'),
    (@bounceTypeID, 'unknown( or illegal)? user( account)?'),
    (@bounceTypeID, 'unrecognized recipient'),
    (@bounceTypeID, 'unregistered address'),
    (@bounceTypeID, 'user (unknown|(does not|doesn\'t) exist)'),
    (@bounceTypeID, 'user doesn\'t have an? \w+ account'),
    (@bounceTypeID, 'user(\'s e-?mail name is)? not found'),
    (@bounceTypeID, '^Validation failed for:'),
    (@bounceTypeID, '5.1.0 Address rejected'),
    (@bounceTypeID, 'no valid recipients?'),
    (@bounceTypeID, 'RecipNotFound'),
    (@bounceTypeID, 'no one at this address'),
    (@bounceTypeID, 'misconfigured forwarding address'),
    (@bounceTypeID, 'account is not allowed'),
    (@bounceTypeID, 'Address .<[^>]*>. not known here'),
    (@bounceTypeID, '{literal}Recipient address rejected: ([a-zA-Z0-9-]+\\.)+[a-zA-Z]{2,}{/literal}'),
    (@bounceTypeID, 'Non sono riuscito a trovare l.indirizzo e-mail'),
    (@bounceTypeID, 'nadie con esta direcci..?n'),
    (@bounceTypeID, 'ni bilo mogo..?e najti prejemnikovega e-po..?tnega naslova'),
    (@bounceTypeID, 'Elektronski naslov (je ukinjen|ne obstaja)'),
    (@bounceTypeID, 'nepravilno nastavljen predal');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Loop', '{ts escape="sql"}Mail routing error{/ts}', 3);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Loop';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, '(mail( forwarding)?|routing).loop'),
    (@bounceTypeID, 'excessive recursion'),
    (@bounceTypeID, 'loop detected'),
    (@bounceTypeID, 'maximum hop count exceeded'),
    (@bounceTypeID, 'message was forwarded more than the maximum allowed times'),
    (@bounceTypeID, 'too many (hops|recursive forwards)');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Quota', '{ts escape="sql"}User inbox is full{/ts}', 3);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Quota';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, '(disk(space)?|over the allowed|exceed(ed|s)?|storage) quota'),
    (@bounceTypeID, '522_mailbox_full'),
    (@bounceTypeID, 'exceeds allowed message count'),
    (@bounceTypeID, 'file too large'),
    (@bounceTypeID, 'full mailbox'),
    (@bounceTypeID, '(mail|in)(box|folder) ((for user \\w+ )?is )?full'),
    (@bounceTypeID, 'mailbox (has exceeded|is over) the limit'),
    (@bounceTypeID, 'mailbox( exceeds allowed)? size'),
    (@bounceTypeID, 'no space left for this user'),
    (@bounceTypeID, 'over\\s?quota'),
    (@bounceTypeID, 'quota (for the mailbox )?has been exceeded'),
    (@bounceTypeID, 'quota ?(usage|violation|exceeded)'),
    (@bounceTypeID, 'recipient storage full'),
    (@bounceTypeID, 'not able to receive more mail'),
    (@bounceTypeID, 'doesn.t have enough disk space left'),
    (@bounceTypeID, 'exceeded storage allocation'),
    (@bounceTypeID, 'running out of disk space');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Relay', '{ts escape="sql"}Unable to reach destination mail server{/ts}', 3);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Relay';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, 'cannot find your hostname'),
    (@bounceTypeID, 'ip name lookup'),
    (@bounceTypeID, 'not configured to relay mail'),
    (@bounceTypeID, 'relay(ing)? (not permitted|(access )?denied)'),
    (@bounceTypeID, 'relayed mail to .+? not allowed'),
    (@bounceTypeID, 'sender ip must resolve'),
    (@bounceTypeID, 'unable to relay'),
    (@bounceTypeID, 'No route to host'),
    (@bounceTypeID, 'Network is unreachable'),
    (@bounceTypeID, 'unrouteable address'),
    (@bounceTypeID, 'We don.t handle mail for'),
    (@bounceTypeID, 'we do not relay'),
    (@bounceTypeID, 'Rejected by next-hop'),
    (@bounceTypeID, 'not permitted to( *550)? relay through this server');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Spam', '{ts escape="sql"}Message caught by a content filter{/ts}', 1);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Spam';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, '(bulk( e-?mail)|content|attachment blocking|virus|mail system) filters?'),
    (@bounceTypeID, '(hostile|questionable|unacceptable) content'),
    (@bounceTypeID, 'address .+? has not been verified'),
    (@bounceTypeID, 'anti-?spam (polic\w+|software)'),
    (@bounceTypeID, 'anti-?virus gateway has detected'),
    (@bounceTypeID, 'blacklisted'),
    (@bounceTypeID, 'blocked message'),
    (@bounceTypeID, 'content control'),
    (@bounceTypeID, 'delivery not authorized'),
    (@bounceTypeID, 'does not conform to our e-?mail policy'),
    (@bounceTypeID, 'excessive spam content'),
    (@bounceTypeID, 'message looks suspicious'),
    (@bounceTypeID, 'open relay'),
    (@bounceTypeID, 'sender was rejected'),
    (@bounceTypeID, 'spam(check| reduction software| filters?)'),
    (@bounceTypeID, 'blocked by a user configured filter'),
    (@bounceTypeID, '(detected|rejected) (as|due to) spam'),
    (@bounceTypeID, 'X-HmXmrOriginalRecipient'),
    (@bounceTypeID, 'Client host .[^ ]*. blocked'),
    (@bounceTypeID, 'automatic(ally-generated)? messages are not accepted'),
    (@bounceTypeID, 'denied by policy'),
    (@bounceTypeID, 'has no corresponding reverse \\(PTR\\) address'),
    (@bounceTypeID, 'has a policy that( [^ ]*)? prohibited the mail that you sent'),
    (@bounceTypeID, 'is likely unsolicited mail'),
    (@bounceTypeID, 'Local Policy Violation'),
    (@bounceTypeID, 'ni bilo mogo..?e dostaviti zaradi varnostnega pravilnika'),
    (@bounceTypeID, 'abuse report');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Syntax', '{ts escape="sql"}Error in SMTP transaction{/ts}', 3);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Syntax';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, 'nonstandard smtp line terminator'),
    (@bounceTypeID, 'syntax error in from address'),
    (@bounceTypeID, 'unknown smtp code');

-- add sample and reserved profiles

INSERT INTO civicrm_uf_group
    (id, name,                 group_type,           title,                                      is_cms_user, is_reserved, help_post) VALUES
    (1,  'name_and_address',   'Individual,Contact',  '{ts escape="sql"}Name and Address{/ts}',   0,           0,           NULL),
    (2,  'supporter_profile',  'Individual,Contact',  '{ts escape="sql"}Supporter Profile{/ts}',  2,           0,           '<p><strong>{ts escape="sql"}The information you provide will NOT be shared with any third party organisations.{/ts}</strong></p><p>{ts escape="sql"}Thank you for getting involved in our campaign!{/ts}</p>'),
    (3,  'participant_status', 'Participant',         '{ts escape="sql"}Participant Status{/ts}',             0,      1,           NULL),
    (4,  'new_individual',     'Individual,Contact',  '{ts escape="sql"}New Individual{/ts}'    ,             0,      1,           NULL),
    (5,  'new_organization',   'Organization,Contact','{ts escape="sql"}New Organization{/ts}'  ,             0,      1,           NULL),
    (6,  'new_household',      'Household,Contact',   '{ts escape="sql"}New Household{/ts}'     ,             0,      1,           NULL),
    (7,  'summary_overlay',    'Contact',             '{ts escape="sql"}Summary Overlay{/ts}'   ,             0,      1,           NULL),
    (8,  'shared_address',     'Contact',             '{ts escape="sql"}Shared Address{/ts}'                , 0,      1,           NULL),
    (9,  'on_behalf_organization', 'Contact,Organization','{ts escape="sql"}On Behalf Of Organization{/ts}',  0,      1,           NULL),
    (10, 'contribution_batch_entry', 'Contribution', '{ts escape="sql"}Contribution Bulk Entry{/ts}' ,       0,      1,           NULL),
    (11, 'membership_batch_entry', 'Membership',     '{ts escape="sql"}Membership Bulk Entry{/ts}' ,         0,      1,           NULL),
    (12, 'event_registration', 'Individual,Contact', '{ts escape="sql"}Your Registration Info{/ts}',         0,      0,           NULL),
    (13, 'honoree_individual', 'Individual,Contact', '{ts escape="sql"}Honoree Individual{/ts}',             0,      1,           NULL);


INSERT INTO civicrm_uf_join
   (is_active,module,entity_table,entity_id,weight,uf_group_id)
VALUES
   (1, 'User Registration',NULL, NULL,1,1),
   (1, 'User Account', NULL, NULL, 1, 1),
   (1, 'Profile', NULL, NULL, 1, 1),
   (1, 'Profile', NULL, NULL, 2, 2),
   (1, 'Profile', NULL, NULL, 11, 12);

INSERT INTO civicrm_uf_field
       ( uf_group_id, field_name,              is_required, is_reserved, weight, visibility,                  in_selector, is_searchable, location_type_id, label,                                          field_type,    help_post, phone_type_id ) VALUES
       (  1,           'first_name',            1,           0,           1,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}First Name{/ts}',               'Individual',   NULL,  NULL),
       (  1,           'last_name',             1,           0,           2,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Last Name{/ts}',                'Individual',   NULL,  NULL),
       (  1,           'street_address',        0,           0,           3,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Street Address (Home){/ts}',    'Contact',      NULL,  NULL),
       (  1,           'city',                  0,           0,           4,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}City (Home){/ts}',              'Contact',      NULL,  NULL),
       (  1,           'postal_code',           0,           0,           5,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Postal Code (Home){/ts}',       'Contact',      NULL,  NULL),
       (  1,           'country',               0,           0,           6,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Country (Home){/ts}',           'Contact',      NULL,  NULL),
       (  1,           'state_province',        0,           0,           7,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}State (Home){/ts}',             'Contact',      NULL,  NULL),
       (  2,           'first_name',            1,           0,           1,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}First Name{/ts}',               'Individual',   NULL,  NULL),
       (  2,           'last_name',             1,           0,           2,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Last Name{/ts}',                'Individual',   NULL,  NULL),
       (  2,           'email',                 1,           0,           3,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Email Address{/ts}',            'Contact',      NULL,  NULL),
       (  3,           'participant_status',    1,           1,           1,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Participant Status{/ts}',       'Participant',  NULL,  NULL),
       (  4,           'first_name',            1,           0,           1,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}First Name{/ts}',               'Individual',   NULL,  NULL),
       (  4,           'last_name',             1,           0,           2,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Last Name{/ts}',                'Individual',   NULL,  NULL),
       (  4,           'email',                 0,           0,           3,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Email Address{/ts}',            'Contact',      NULL,  NULL),
       (  5,           'organization_name',     1,           0,           2,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Organization Name{/ts}',        'Organization', NULL,  NULL),
       (  5,           'email',                 0,           0,           3,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Email Address{/ts}',            'Contact',      NULL,  NULL),
       (  6,           'household_name',        1,           0,           2,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Household Name{/ts}',           'Household',    NULL,  NULL),
       (  6,           'email',                 0,           0,           3,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Email Address{/ts}',            'Contact',      NULL,  NULL),
       (  7,           'phone',                 1,           0,           1,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Home Phone{/ts}',               'Contact',      NULL,  1 ),
       (  7,           'phone',                 1,           0,           2,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Home Mobile{/ts}',              'Contact',      NULL,  2 ),
       (  7,           'street_address',        1,           0,           3,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Primary Address{/ts}',          'Contact',      NULL,  NULL),
       (  7,           'city',                  1,           0,           4,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}City{/ts}',                     'Contact',      NULL,  NULL),
       (  7,           'state_province',        1,           0,           5,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}State{/ts}',                    'Contact',      NULL,  NULL),
       (  7,           'postal_code',           1,           0,           6,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Postal Code{/ts}',              'Contact',      NULL,  NULL),
       (  7,           'email',                 1,           0,           7,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Primary Email{/ts}',            'Contact',      NULL,  NULL),
       (  7,           'group',                 1,           0,           8,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Groups{/ts}',                   'Contact',      NULL,  NULL),
       (  7,           'tag',                   1,           0,           9,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Tags{/ts}',                     'Contact',      NULL,  NULL),
       (  7,           'gender_id',             1,           0,           10,     'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Gender{/ts}',                   'Individual',   NULL,  NULL),
       (  7,           'birth_date',            1,           0,           11,     'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Date of Birth{/ts}',            'Individual',   NULL,  NULL),
       (  8,           'street_address',        1,           1,           1,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Street Address (Home){/ts}',    'Contact',      NULL,  NULL),
       (  8,           'city',                  1,           1,           2,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}City (Home){/ts}',              'Contact',      NULL,  NULL),
       (  8,           'postal_code',           0,           0,           3,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Postal Code (Home){/ts}',       'Contact',      NULL,  NULL),
       (  8,           'country',               0,           0,           4,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Country (Home){/ts}',           'Contact',      NULL,  NULL),
       (  8,           'state_province',        0,           0,           5,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}State (Home){/ts}',             'Contact',      NULL,  NULL),
       (  9,           'organization_name',     1,           0,           1,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Organization Name{/ts}',        'Organization', NULL,  NULL),
       (  9,           'phone',                 1,           0,           2,      'User and User Admin Only',  0,           0,             3,             '{ts escape="sql"}Phone (Main) {/ts}',            'Contact',      NULL,   1),
       (  9,           'email',                 1,           0,           3,      'User and User Admin Only',  0,           0,             3,             '{ts escape="sql"}Email (Main) {/ts}',            'Contact',      NULL,   NULL),
       (  9,           'street_address',        1,           0,           4,      'User and User Admin Only',  0,           0,             3,             '{ts escape="sql"}Street Address{/ts}',           'Contact',      NULL,   NULL),
       (  9,           'city',                  1,           0,           5,      'User and User Admin Only',  0,           0,             3,             '{ts escape="sql"}City{/ts}',                     'Contact',      NULL,   NULL),
       (  9,           'postal_code',           1,           0,           6,      'User and User Admin Only',  0,           0,             3,             '{ts escape="sql"}Postal Code{/ts}',              'Contact',      NULL,   NULL),
       (  9,           'country',               1,           0,           7,      'User and User Admin Only',  0,           0,             3,             '{ts escape="sql"}Country{/ts}',                  'Contact',      NULL,   NULL),
       (  9,           'state_province',        1,           0,           8,      'User and User Admin Only',  0,           0,             3,             '{ts escape="sql"}State/Province{/ts}',         'Contact',      NULL,   NULL),
       ( 10,     'financial_type',              0, 1, 1, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Financial Type{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'total_amount',                0, 1, 2, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Amount{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'contribution_status_id',      1, 1, 3, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Status{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'receive_date',                1, 1, 4, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Contribution Date{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'contribution_source',         0, 0, 5, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Contribution Source{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'payment_instrument',          0, 0, 6, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Payment Method{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'contribution_check_number',                0, 0, 7, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Check Number{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'send_receipt',                0, 0, 8, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Send Receipt{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'invoice_id',                  0, 0, 9, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Invoice ID{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'soft_credit',                 0, 0, 10, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Soft Credit{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'soft_credit_type',            0, 0, 11, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Soft Credit Type{/ts}', 'Contribution', NULL, NULL ),
       ( 11,     'membership_type',             1, 1, 1, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Membership Type{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'membership_join_date',        1, 1, 2, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Member Since{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'membership_start_date',       0, 1, 3, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Start Date{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'membership_end_date',         0, 1, 4, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}End Date{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'membership_source',           0, 0, 5, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Membership Source{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'send_receipt',                0, 0, 6, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Send Receipt{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'financial_type',              0, 1, 7, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Financial Type{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'total_amount',                0, 1, 8, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Amount{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'receive_date',                1, 1, 9, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Contribution Date{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'payment_instrument',          0, 0, 10, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Payment Method{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'contribution_check_number',                0, 0, 11, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Check Number{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'contribution_status_id',      1, 1, 12, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Payment Status{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'soft_credit',                 0, 0, 13, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Soft Credit{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'soft_credit_type',            0, 0, 14, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Soft Credit Type{/ts}', 'Membership', NULL, NULL ),
       ( 12,     'first_name',                  1, 0,  1, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}First Name{/ts}',  'Individual', NULL, NULL),
       ( 12,     'last_name',                   1, 0,  2, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Last Name{/ts}',   'Individual',  NULL,  NULL),
       ( 12,     'email',                       1, 0,  3, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Email Address{/ts}', 'Contact', NULL, NULL),
       ( 13,     'prefix_id',                   0, 1,  1, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Individual Prefix{/ts}', 'Individual', NULL, NULL),
       ( 13,     'first_name',                  1, 1,  2, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}First Name{/ts}',        'Individual', NULL, NULL),
       ( 13,     'last_name',                   1, 1,  3, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Last Name{/ts}',         'Individual', NULL, NULL),
       ( 13,     'email',                       0, 1,  4, 'User and User Admin Only', 0, 0, 1,    '{ts escape="sql"}Email Address{/ts}',     'Contact', NULL, NULL);


INSERT INTO civicrm_participant_status_type
  (id, name,                                  label,                                                       class,      is_reserved, is_active, is_counted, weight, visibility_id) VALUES
  (1,  'Registered',                          '{ts escape="sql"}Registered{/ts}',                          'Positive', 1,           1,         1,          1,      1            ),
  (2,  'Attended',                            '{ts escape="sql"}Attended{/ts}',                            'Positive', 0,           1,         1,          2,      2            ),
  (3,  'No-show',                             '{ts escape="sql"}No-show{/ts}',                             'Negative', 0,           1,         0,          3,      2            ),
  (4,  'Cancelled',                           '{ts escape="sql"}Cancelled{/ts}',                           'Negative', 1,           1,         0,          4,      2            ),
  (5,  'Pending from pay later',              '{ts escape="sql"}Pending (pay later){/ts}',                 'Pending',  1,           1,         1,          5,      2            ),
  (6,  'Pending from incomplete transaction', '{ts escape="sql"}Pending (incomplete transaction){/ts}',    'Pending',  1,           1,         0,          6,      2            ),
  (7,  'On waitlist',                         '{ts escape="sql"}On waitlist{/ts}',                         'Waiting',  1,           0,         0,          7,      2            ),
  (8,  'Awaiting approval',                   '{ts escape="sql"}Awaiting approval{/ts}',                   'Waiting',  1,           0,         1,          8,      2            ),
  (9,  'Pending from waitlist',               '{ts escape="sql"}Pending from waitlist{/ts}',               'Pending',  1,           0,         1,          9,      2            ),
  (10, 'Pending from approval',               '{ts escape="sql"}Pending from approval{/ts}',               'Pending',  1,           0,         1,          10,     2            ),
  (11, 'Rejected',                            '{ts escape="sql"}Rejected{/ts}',                            'Negative', 1,           0,         0,          11,     2            ),
  (12, 'Expired',                             '{ts escape="sql"}Expired{/ts}',                             'Negative', 1,           1,         0,          12,     2            ),
  (13, 'Pending in cart',                     '{ts escape="sql"}Pending in cart{/ts}',                     'Pending',  1,           1,         0,          13,     2            ),
  (14,  'Partially paid',                      '{ts escape="sql"}Partially paid{/ts}',                      'Positive', 1,           1,         1,          14,     2           ),
  (15,  'Pending refund',                      '{ts escape="sql"}Pending refund{/ts}',                      'Positive', 1,           1,         1,          15,     2           ),
  (16,  'Transferred',                         '{ts escape="sql"}Transferred{/ts}',                         'Negative', 1, 1, 0, 16, 2);

-- CRM-8150
INSERT INTO civicrm_action_mapping
(entity, entity_value, entity_value_label, entity_status, entity_status_label, entity_date_start, entity_date_end, entity_recipient)
VALUES
( 'civicrm_activity', 'activity_type', 'Activity Type', 'activity_status', 'Activity Status', 'activity_date_time', NULL, 'activity_contacts'),
( 'civicrm_participant', 'event_type', 'Event Type', 'civicrm_participant_status_type', 'Participant Status', 'event_start_date', 'event_end_date', 'event_contacts'),
( 'civicrm_participant', 'civicrm_event', 'Event Name', 'civicrm_participant_status_type', 'Participant Status', 'event_start_date', 'event_end_date', 'event_contacts'),
( 'civicrm_membership', 'civicrm_membership_type', 'Membership Type', 'auto_renew_options', 'Auto Renew Options', 'membership_join_date', 'membership_end_date', NULL),
( 'civicrm_participant', 'event_template', 'Event Template', 'civicrm_participant_status_type', 'Participant Status', 'event_start_date', 'event_end_date', 'event_contacts'),
( 'civicrm_contact', 'civicrm_contact', 'Date Field', 'contact_date_reminder_options', 'Annual Options', 'date_field', NULL, NULL);

INSERT INTO `civicrm_contact_type`
  (`id`, `name`, `label`,`image_URL`, `parent_id`, `is_active`,`is_reserved`, `icon`)
 VALUES
  ( 1, 'Individual'  , '{ts escape="sql"}Individual{/ts}'  , NULL, NULL, 1, 1, 'fa-user'),
  ( 2, 'Household'   , '{ts escape="sql"}Household{/ts}'   , NULL, NULL, 1, 1, 'fa-home'),
  ( 3, 'Organization', '{ts escape="sql"}Organization{/ts}', NULL, NULL, 1, 1, 'fa-building');

{include file='civicrm_msg_template.tpl'}

-- CRM-8358

INSERT INTO `civicrm_job`
    ( domain_id, run_frequency, last_run, name, description, api_entity, api_action, parameters, is_active )
VALUES
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}CiviCRM Update Check{/ts}', '{ts escape="sql" skip="true"}Checks for CiviCRM version updates. Important for keeping the database secure. Also sends anonymous usage statistics to civicrm.org to to assist in prioritizing ongoing development efforts.{/ts}', 'job', 'version_check', NULL, 1),
    ( @domainID, 'Always' , NULL, '{ts escape="sql" skip="true"}Send Scheduled Mailings{/ts}', '{ts escape="sql" skip="true"}Sends out scheduled CiviMail mailings{/ts}', 'job', 'process_mailing', NULL, 0),
    ( @domainID, 'Hourly' , NULL, '{ts escape="sql" skip="true"}Fetch Bounces{/ts}', '{ts escape="sql" skip="true"}Fetches bounces from mailings and writes them to mailing statistics{/ts}', 'job', 'fetch_bounces', NULL, 0),
    ( @domainID, 'Hourly' , NULL, '{ts escape="sql" skip="true"}Process Inbound Emails{/ts}', '{ts escape="sql" skip="true"}Inserts activity for a contact or a case by retrieving inbound emails from a mail directory{/ts}', 'job', 'fetch_activities', NULL, 0),
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}Process Pledges{/ts}', '{ts escape="sql" skip="true"}Updates pledge records and sends out reminders{/ts}', 'job', 'process_pledge','{ts escape="sql" skip="true"}send_reminders=[1 or 0] optional- 1 to send payment reminders{/ts}', 0),
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}Geocode and Parse Addresses{/ts}',  '{ts escape="sql" skip="true"}Retrieves geocodes (lat and long) and / or parses street addresses (populates street number, street name, etc.){/ts}', 'job', 'geocode', '{ts escape="sql" skip="true"}geocoding=[1 or 0] required
parse=[1 or 0] required
start=[contact ID] optional-begin with this contact ID
end=[contact ID] optional-process contacts with IDs less than this
throttle=[1 or 0] optional-1 adds five second sleep{/ts}', 0),
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}Update Greetings and Addressees{/ts}','{ts escape="sql" skip="true"}Goes through contact records and updates email and postal greetings, or addressee value{/ts}', 'job', 'update_greeting','{ts escape="sql" skip="true"}ct=[Individual or Household or Organization] required
gt=[email_greeting or postal_greeting or addressee] required
force=[0 or 1] optional-0 update contacts with null value, 1 update all
limit=Number optional-Limit the number of contacts to update{/ts}', 0),
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}Mail Reports{/ts}', '{ts escape="sql" skip="true"}Generates and sends out reports via email{/ts}', 'job', 'mail_report','{ts escape="sql" skip="true"}instanceId=[ID of report instance] required
format=[csv or print] optional-output CSV or print-friendly HTML, else PDF{/ts}', 0),
    ( @domainID, 'Hourly' ,  NULL, '{ts escape="sql" skip="true"}Send Scheduled Reminders{/ts}', '{ts escape="sql" skip="true"}Sends out scheduled reminders via email{/ts}', 'job', 'send_reminder', NULL, 0),
    ( @domainID, 'Always' , NULL, '{ts escape="sql" skip="true"}Update Participant Statuses{/ts}', '{ts escape="sql" skip="true"}Updates pending event participant statuses based on time{/ts}', 'job', 'process_participant', NULL, 0),
    ( @domainID, 'Daily' , NULL, '{ts escape="sql" skip="true"}Update Membership Statuses{/ts}', '{ts escape="sql" skip="true"}Updates membership statuses. WARNING: Membership renewal reminders have been migrated to the Schedule Reminders functionality, which supports multiple renewal reminders.{/ts}', 'job', 'process_membership',   NULL, 0),
    ( @domainID, 'Always' , NULL, '{ts escape="sql" skip="true"}Process Survey Respondents{/ts}',   '{ts escape="sql" skip="true"}Releases reserved survey respondents when they have been reserved for longer than the Release Frequency days specified for that survey.{/ts}', 'job', 'process_respondent',NULL, 0),
    ( @domainID, 'Hourly' , NULL, '{ts escape="sql" skip="true"}Clean-up Temporary Data and Files{/ts}','{ts escape="sql" skip="true"}Removes temporary data and files, and clears old data from cache tables. Recommend running this job every hour to help prevent database and file system bloat.{/ts}', 'job', 'cleanup', NULL, 0),
    ( @domainID, 'Always' , NULL, '{ts escape="sql" skip="true"}Send Scheduled SMS{/ts}',           '{ts escape="sql" skip="true"}Sends out scheduled SMS{/ts}', 'job', 'process_sms',             NULL, 0),
    ( @domainID, 'Always' , NULL, '{ts escape="sql" skip="true"}Rebuild Smart Group Cache{/ts}', '{ts escape="sql" skip="true"}Rebuilds the smart group cache.{/ts}', 'job', 'group_rebuild', '{ts escape="sql" skip="true"}limit=Number optional-Limit the number of smart groups rebuild{/ts}', 0),
    ( @domainID, 'Daily' , NULL, '{ts escape="sql" skip="true"}Disable expired relationships{/ts}','{ts escape="sql" skip="true"}Disables relationships that have expired (ie. those relationships whose end date is in the past).{/ts}', 'job', 'disable_expired_relationships', NULL, 0),
    ( @domainID, 'Daily' , NULL, '{ts escape="sql" skip="true"}Validate Email Address from Mailings.{/ts}', '{ts escape="sql" skip="true"}Updates the reset_date on an email address to indicate that there was a valid delivery to this email address.{/ts}', 'mailing', 'update_email_resetdate', '{ts escape="sql" skip="true"}minDays, maxDays=Consider mailings that have completed between minDays and maxDays{/ts}', 0);

SELECT @option_value_rel_id  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Income Account is';
SELECT @option_value_rel_id_exp  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Expense Account is';
SELECT @option_value_rel_id_ar  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Accounts Receivable Account is';
SELECT @option_value_rel_id_as  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Asset Account is';
SELECT @option_value_rel_id_cg  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Cost of Sales Account is';
SELECT @option_value_rel_id_dr  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Deferred Revenue Account is';

SELECT @financial_type_id_dtn          := max(id) FROM civicrm_financial_type WHERE name = '{ts escape="sql"}Donation{/ts}';
SELECT @financial_type_id_md         := max(id) FROM civicrm_financial_type WHERE name = '{ts escape="sql"}Member Dues{/ts}';
SELECT @financial_type_id_cc         := max(id) FROM civicrm_financial_type WHERE name = '{ts escape="sql"}Campaign Contribution{/ts}';
SELECT @financial_type_id_ef         := max(id) FROM civicrm_financial_type WHERE name = '{ts escape="sql"}Event Fee{/ts}';

SELECT @financial_account_id_dtn       := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Donation{/ts}';
SELECT @financial_account_id_md         := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Member Dues{/ts}';
SELECT @financial_account_id_cc         := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Campaign Contribution{/ts}';
SELECT @financial_account_id_ef         := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Event Fee{/ts}';
SELECT @financial_account_id_bf         := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Banking Fees{/ts}';
SELECT @financial_account_id_ap        := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Accounts Receivable{/ts}';
SELECT @financial_account_id_ar        := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Deposit Bank Account{/ts}';
SELECT @financial_account_id_pp        := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Payment Processor Account{/ts}';
SELECT @financial_account_id_pr        := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Premiums{/ts}';
SELECT @financial_account_id_dref      := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Deferred Revenue - Event Fee{/ts}';
SELECT @financial_account_id_drmd      := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Deferred Revenue - Member Dues{/ts}';

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

-- Auto-install core extension.
-- Note this is a limited interim technique for installing core extensions -  the goal is that core extensions would be installed
-- in the setup routine based on their tags & using the standard extension install api.
-- do not try this at home folks.
INSERT IGNORE INTO civicrm_extension (type, full_name, name, label, file, is_active) VALUES ('module', 'sequentialcreditnotes', 'Sequential credit notes', 'Sequential credit notes', 'sequentialcreditnotes', 1);
INSERT IGNORE INTO civicrm_extension (type, full_name, name, label, file, is_active) VALUES ('module', 'greenwich', 'Theme: Greenwich', 'Theme: Greenwich', 'greenwich', 1);
INSERT IGNORE INTO civicrm_extension (type, full_name, name, label, file, is_active) VALUES ('module', 'eventcart', 'Event cart', 'Event cart', 'eventcart', 1);
INSERT IGNORE INTO civicrm_extension (type, full_name, name, label, file, is_active) VALUES ('module', 'financialacls', 'Financial ACLs', 'Financial ACLs', 'financialacls', 1);
INSERT IGNORE INTO civicrm_extension (type, full_name, name, label, file, is_active) VALUES ('module', 'recaptcha', 'reCAPTCHA', 'reCAPTCHA', 'recaptcha', 1);
INSERT IGNORE INTO civicrm_extension (type, full_name, name, label, file, is_active) VALUES ('module', 'ckeditor4', 'CKEditor4', 'CKEditor4', 'ckeditor4', 1);
INSERT IGNORE INTO civicrm_extension (type, full_name, name, label, file, is_active) VALUES ('module', 'legacycustomsearches', 'Custom search framework', 'Custom search framework', 'legacycustomsearches', 1);
INSERT IGNORE INTO civicrm_extension (type, full_name, name, label, file, is_active) VALUES ('module', 'org.civicrm.flexmailer', 'FlexMailer', 'FlexMailer', 'flexmailer', 1);

{php}echo $optionGroups['soft_credit_type']->toSQL();{/php}
{php}echo $optionGroups['recent_items_providers']->toSQL();{/php}
