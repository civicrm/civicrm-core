--CRM-7111 , Unique constraints for table `civicrm_line_item`

 ALTER TABLE `civicrm_line_item` ADD UNIQUE INDEX UI_line_item_value (`entity_table`,`entity_id`,`price_field_value_id`,`price_field_id`);