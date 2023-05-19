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

{php}
  echo (include "sql/civicrm_data/civicrm_location_type.sqldata.php")->toSQL();
  echo (include "sql/civicrm_data/civicrm_relationship_type.sqldata.php")->toSQL();
  echo (include "sql/civicrm_data/civicrm_tag.sqldata.php")->toSQL();
  echo (include "sql/civicrm_data/civicrm_mailing_component.sqldata.php")->toSQL();
  echo (include "sql/civicrm_data/civicrm_financial_type.sqldata.php")->toSQL();

  $optionGroups = include 'sql/civicrm_data/civicrm_option_group.php';
  $laterGroups = ['encounter_medium', 'soft_credit_type', 'recent_items_providers'];
  foreach ($optionGroups as $groupName => $group) {
    if (!in_array($groupName, $laterGroups)) {
      echo $group->toSQL();
    }
  }
{/php}

-- financial accounts
SELECT @option_group_id_fat            := max(id) from civicrm_option_group where name = 'financial_account_type';
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

{php}
  echo $optionGroups['encounter_medium']->toSQL();
  echo (include "sql/civicrm_data/civicrm_membership_status.sqldata.php")->toSQL();
  echo (include "sql/civicrm_data/civicrm_preferences_date.sqldata.php")->toSQL();
  echo (include "sql/civicrm_data/civicrm_payment_processor_type.sqldata.php")->toSQL();
{/php}


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

{php}echo (include "sql/civicrm_data/civicrm_county.sqldata.php")->toSQL();{/php}

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

{php}echo (include "sql/civicrm_data/civicrm_uf_group.sqldata.php")->toSQL();{/php}
{php}echo (include "sql/civicrm_data/civicrm_uf_join.sqldata.php")->toSQL();{/php}
{php}echo (include "sql/civicrm_data/civicrm_uf_field.sqldata.php")->toSQL();{/php}
{php}echo (include "sql/civicrm_data/civicrm_participant_status_type.sqldata.php")->toSQL();{/php}
{php}echo (include "sql/civicrm_data/civicrm_action_mapping.sqldata.php")->toSQL();{/php}
{php}echo (include "sql/civicrm_data/civicrm_contact_type.sqldata.php")->toSQL();{/php}

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

SELECT @option_group_id_arel           := max(id) from civicrm_option_group where name = 'account_relationship';
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
