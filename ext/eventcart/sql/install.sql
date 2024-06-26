ALTER TABLE civicrm_participant ADD CONSTRAINT
  `FK_civicrm_participant_cart_id`
  FOREIGN KEY (`cart_id`)
    REFERENCES `civicrm_event_carts`(`ID`);
