-- CRM-1971

ALTER TABLE civicrm_event
   DROP FOREIGN KEY `FK_civicrm_event_loc_block_id`;
ALTER TABLE civicrm_event
   ADD CONSTRAINT `FK_civicrm_event_loc_block_id` FOREIGN KEY (`loc_block_id`) REFERENCES `civicrm_loc_block` (`id`) ON DELETE SET NULL;

ALTER TABLE civicrm_loc_block DROP name;
