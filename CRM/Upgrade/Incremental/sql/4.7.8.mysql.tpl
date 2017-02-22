{* file to handle db changes in 4.7.8 during upgrade *}

-- CRM-18516 Convert the date fields relating to group caching and acl caching timestamp
ALTER TABLE civicrm_group CHANGE cache_date cache_date timestamp NULL DEFAULT NULL , CHANGE refresh_date refresh_date timestamp NULL DEFAULT NULL;
ALTER TABLE civicrm_acl_cache CHANGE modified_date modified_date timestamp NULL DEFAULT NULL;

-- CRM-18537
DELETE FROM civicrm_state_province WHERE name = 'Fernando de Noronha';

-- CRM-17118 extend civicrm_address postal_code to accept full data strings from paypal etc.
ALTER TABLE civicrm_address CHANGE `postal_code` `postal_code` varchar(64) ;
