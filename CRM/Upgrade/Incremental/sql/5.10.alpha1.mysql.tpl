{* file to handle db changes in 5.10.alpha1 during upgrade *}

{* Continuation from CRM-6405 it appears no upgrade step was done back in 3.2 *}
UPDATE civicrm_mailing_bounce_type SET hold_threshold = 30 WHERE name = 'Away' AND hold_threshold = 3;
