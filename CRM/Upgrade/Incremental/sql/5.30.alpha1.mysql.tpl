{* file to handle db changes in 5.30.alpha1 during upgrade *}
-- Allow self-service/transfer to have a negative time.
ALTER TABLE civicrm_event MODIFY COLUMN selfcancelxfer_time INT;

