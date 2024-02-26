{* file to handle db changes in 5.72.alpha1 during upgrade *}
UPDATE civicrm_event SET selfcancelxfer_time = 0 WHERE selfcancelxfer_time IS NULL;
