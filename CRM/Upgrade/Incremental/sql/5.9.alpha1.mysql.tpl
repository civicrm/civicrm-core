{* file to handle db changes in 5.9.alpha1 during upgrade *}

-- mail/issues#23 Bounce processing doesn't catch pattern "user doesn't exist"
SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Invalid';
UPDATE civicrm_mailing_bounce_pattern SET pattern = 'user (unknown|(does not|doesn\'t) exist)' WHERE bounce_type_id = @bounceTypeID AND pattern = 'user (unknown|does not exist)';
