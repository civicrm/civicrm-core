{* file to handle db changes in 6.0.beta1 during upgrade *}

{* Note: The unfortunate status-quo is that Navigation labels store untranslated strings, so no `ts` needed (see https://issues.civicrm.org/jira/browse/CRM-6998 FWIW).*}
UPDATE civicrm_navigation
SET label = 'Site Email Addresses'
WHERE label = 'FROM Email Addresses';
