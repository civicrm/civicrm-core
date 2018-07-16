{* file to handle db changes in 5.4.alpha1 during upgrade *}

{*
v4.7.20 updated these colums so that new installs would default to TIMESTAMP instead of DATETIME.
Status-checks and DoctorWhen have been encouraging a transition, but it wasn't mandated, and there
was little urgency... because `expired_date` was ignored, and adhoc TTLs on `created_date` had
generally long windows. Now that we're using `expired_date` in more important ways for 5.4,
we want to ensure that these values are handled precisely and consistently.
*}

ALTER TABLE civicrm_cache
  CHANGE created_date created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When was the cache item created',
  CHANGE expired_date expired_date  TIMESTAMP NULL DEFAULT NULL COMMENT 'When should the cache item expire';


-- core/issues/230
ALTER TABLE `civicrm_saved_search`
  DROP FOREIGN KEY FK_civicrm_saved_search_mapping_id;

ALTER TABLE `civicrm_saved_search`
  ADD CONSTRAINT `FK_civicrm_saved_search_mapping_id` FOREIGN KEY (`mapping_id`) REFERENCES `civicrm_mapping` (`id`) ON DELETE SET NULL;