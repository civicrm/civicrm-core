{* file to handle db changes in 5.64.alpha1 during upgrade *}

UPDATE `civicrm_acl` SET `priority` = `id`;

-- fix mis-casing of field name. Note the php function doesn't permit the name change hence it is here
-- but field is not localised.
ALTER TABLE civicrm_uf_group
CHANGE `post_URL` `post_url` varchar(255) DEFAULT NULL COMMENT 'Redirect to URL on submit.',
CHANGE `cancel_URL` `cancel_url` varchar(255) DEFAULT NULL COMMENT 'Redirect to URL when Cancel button clicked.'
;
