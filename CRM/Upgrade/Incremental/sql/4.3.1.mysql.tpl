-- CRM-12351
UPDATE civicrm_dedupe_rule_group SET title = name WHERE title IS NULL;

-- CRM-12373

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Dns';
INSERT INTO civicrm_mailing_bounce_pattern (bounce_type_id, pattern)
    VALUES
      (@bounceTypeID, 'Host or domain name not found');

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Host';
INSERT INTO civicrm_mailing_bounce_pattern (bounce_type_id, pattern)
    VALUES
      (@bounceTypeID, 'lost connection'),
      (@bounceTypeID, 'conversation with [^ ]* timed out while');

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Relay';
INSERT INTO civicrm_mailing_bounce_pattern (bounce_type_id, pattern)
    VALUES
      (@bounceTypeID, 'No route to host'),
      (@bounceTypeID, 'Network is unreachable');
