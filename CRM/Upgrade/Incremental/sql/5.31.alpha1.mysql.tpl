{* file to handle db changes in 5.31.alpha1 during upgrade *}

{* Remove Country & State special select fields *}
UPDATE civicrm_custom_field SET html_type = 'Select'
WHERE html_type IN ('Select Country', 'Select State/Province');
