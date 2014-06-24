{* file to handle db changes in 4.4.6 during upgrade *}
-- CRM-14903
ALTER TABLE `civicrm_mapping_field`
CHANGE COLUMN `operator` `operator` ENUM('=','!=','>','<','>=','<=','IN','NOT IN','LIKE','NOT LIKE','IS NULL','IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY', 'RLIKE');
