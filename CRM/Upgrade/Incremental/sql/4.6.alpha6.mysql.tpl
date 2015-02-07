{* file to handle db changes in 4.6.alpha6 during upgrade *}

UPDATE `civicrm_navigation` SET url = 'civicrm/api' WHERE url = 'civicrm/api/explorer';

-- CRM-15931
UPDATE civicrm_mailing_group SET group_type = 'Include' WHERE group_type = 'include';
UPDATE civicrm_mailing_group SET group_type = 'Exclude' WHERE group_type = 'exclude';
UPDATE civicrm_mailing_group SET group_type = 'Base' WHERE group_type = 'base';

-- CRM-15934
SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Quota';
INSERT INTO civicrm_mailing_bounce_pattern (bounce_type_id, pattern)
    VALUES
      (@bounceTypeID, 'doesn.t have enough disk space left'),
      (@bounceTypeID, 'exceeded storage allocation'),
      (@bounceTypeID, 'running out of disk space');

UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = 'disk(space)?|over the allowed|exceed(ed|s)?|storage) quota' WHERE `id` = 87;
UPDATE `civicrm_mailing_bounce_pattern` SET `pattern` = '(mail|in)(box|folder) ((for user \w+ )?is )?full' WHERE `id` = 92;
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
