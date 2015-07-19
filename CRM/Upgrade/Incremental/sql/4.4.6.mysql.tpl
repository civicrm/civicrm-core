{* CRM-16846 - This file is never run, but it doesn't matter because the below query is undone by another alteration to the same column in 4.5.alpha1 *}

-- CRM-14903
ALTER TABLE `civicrm_mapping_field`
CHANGE COLUMN `operator` `operator` ENUM('=','!=','>','<','>=','<=','IN','NOT IN','LIKE','NOT LIKE','IS NULL','IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY', 'RLIKE');
