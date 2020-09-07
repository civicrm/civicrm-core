{* file to handle db changes in 5.31.alpha1 during upgrade *}

{* Remove Country & State special select fields *}
UPDATE civicrm_custom_field SET html_type = 'Select'
WHERE html_type IN ('Select Country', 'Select State/Province');

{* make period_type required - it already is so the update is precautionary *}
UPDATE civicrm_membership_type SET period_type = 'rolling' WHERE period_type IS NULL;
ALTER TABLE civicrm_membership_type MODIFY `period_type` varchar(8) NOT NULL COMMENT 'Rolling membership period starts on signup date. Fixed membership periods start on fixed_period_start_day.'

