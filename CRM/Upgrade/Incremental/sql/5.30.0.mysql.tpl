{* file to handle db changes in 5.30.0 during upgrade *}

ALTER TABLE civicrm_mail_settings
ADD is_contact_creation_disabled_if_no_match TINYINT default 0 not null comment 'If this option is enabled, CiviCRM will not create new contacts when filing emails';
