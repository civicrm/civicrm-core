{* file to handle db changes in 4.6.beta1 during upgrade *}
-- See https://issues.civicrm.org/jira/browse/CRM-15361
UPDATE civicrm_mailing SET location_type_id = NULL WHERE location_type_id = 0;
ALTER TABLE civicrm_mailing ADD CONSTRAINT FK_civicrm_mailing_location_type_id FOREIGN KEY FK_civicrm_mailing_location_type_id(`location_type_id`) REFERENCES `civicrm_location_type`(`id`) ON DELETE SET NULL;

SELECT @parent_id := id from `civicrm_navigation` where name = 'Customize Data and Screens' AND domain_id = {$domainID};
SELECT @add_weight_id := weight from `civicrm_navigation` where `name` = 'Search Preferences' and `parent_id` = @parent_id;
UPDATE `civicrm_navigation`
SET `weight` = `weight`+1
WHERE `parent_id` = @parent_id
AND `weight` > @add_weight_id;
INSERT INTO `civicrm_navigation`
( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
( {$domainID}, 'civicrm/admin/setting/preferences/date?reset=1', '{ts escape="sql" skip="true"}Date Preferences{/ts}', 'Date Preferences', 'administer CiviCRM', '', @parent_id , '1', NULL, @add_weight_id + 1 );


-- CRM-15934
SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Quota';
INSERT INTO civicrm_mailing_bounce_pattern (bounce_type_id, pattern)
    VALUES
      (@bounceTypeID, 'doesn.t have enough disk space left'),
      (@bounceTypeID, 'exceeded storage allocation'),
      (@bounceTypeID, 'running out of disk space');

UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = '(disk(space)?|over the allowed|exceed(ed|s)?|storage) quota' WHERE `id` = 87;
UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = '(mail|in)(box|folder) ((for user \\w+ )?is )?full' WHERE `id` = 92;
UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = 'mailbox (has exceeded|is over) the limit' WHERE `id` = 93;
UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = 'quota ?(usage|violation|exceeded)' WHERE `id` = 98;

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Inactive';
INSERT INTO civicrm_mailing_bounce_pattern (bounce_type_id, pattern)
    VALUES
      (@bounceTypeID, 'account that you tried to reach is disabled'),
      (@bounceTypeID, 'User banned');

UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = 'not accepting (mail|messages)' WHERE `id` = 37;

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Loop';
UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = '(mail( forwarding)?|routing).loop' WHERE `id` = 81;
UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = 'too many (hops|recursive forwards)' WHERE `id` = 86;

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Relay';
INSERT INTO civicrm_mailing_bounce_pattern (bounce_type_id, pattern)
    VALUES
      (@bounceTypeID, 'unrouteable address'),
      (@bounceTypeID, 'We don.t handle mail for'),
      (@bounceTypeID, 'we do not relay'),
      (@bounceTypeID, 'Rejected by next-hop'),
      (@bounceTypeID, 'not permitted to( *550)? relay through this server');

UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = 'relay(ing)? (not permitted|(access )?denied)' WHERE `id` = 104;

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Host';
INSERT INTO civicrm_mailing_bounce_pattern (bounce_type_id, pattern)
    VALUES
      (@bounceTypeID, 'server requires authentication'),
      (@bounceTypeID, 'authentication (is )?required');

UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = 'server is (down or unreachable|not responding)' WHERE `id` = 20;

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Invalid';
INSERT INTO civicrm_mailing_bounce_pattern (bounce_type_id, pattern)
    VALUES
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

UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = 'address(es)?( you (entered|specified))? (could|was)( not|n.t)( be)? found' WHERE `id` = 44;
UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = 'address(ee)? (unknown|invalid)' WHERE `id` = 45;
UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = '(mail )?delivery (to this user )?is not allowed' WHERE `id` = 59;
UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = 'no such (mail drop|mailbox( \\w+)?|(e-?mail )?address|recipient|(local )?user|person)( here)?' WHERE `id` = 64;
UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = 'no mailbox (here )?by that name' WHERE `id` = 65;
UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = 'recipient (does not exist|(is )?unknown|rejected|denied|not found)' WHERE `id` = 69;
UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = 'unknown (local( |-)part|recipient|address error)' WHERE `id` = 73;

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Spam';
INSERT INTO civicrm_mailing_bounce_pattern (bounce_type_id, pattern)
    VALUES
      (@bounceTypeID, 'Client host .[^ ]*. blocked'),
      (@bounceTypeID, 'automatic(ally-generated)? messages are not accepted'),
      (@bounceTypeID, 'denied by policy'),
      (@bounceTypeID, 'has no corresponding reverse \\(PTR\\) address'),
      (@bounceTypeID, 'has a policy that( [^ ]*)? prohibited the mail that you sent'),
      (@bounceTypeID, 'is likely unsolicited mail'),
      (@bounceTypeID, 'Local Policy Violation'),
      (@bounceTypeID, 'ni bilo mogo..?e dostaviti zaradi varnostnega pravilnika');

UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = '(detected|rejected) as spam' WHERE `id` = 126;
