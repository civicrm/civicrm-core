DROP table civicrm_events_in_carts;
DROP TABLE  civicrm_event_cart;
ALTER TABLE civicrm_participant DROP CONSTRAINT FK_civicrm_participant_cart_id;
