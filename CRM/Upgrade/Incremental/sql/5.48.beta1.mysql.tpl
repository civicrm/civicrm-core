{* file to handle db changes in 5.48.beta1 during upgrade *}

-- Remove entry for Grant Reports since grant is not a component anymore so this url just shows an empty list
DELETE FROM civicrm_navigation WHERE url='civicrm/report/list?compid=5&reset=1';

DELETE FROM civicrm_managed WHERE module = "civigrant" AND entity_type = "OptionValue"
AND name LIKE "OptionGroup_grant_type_%";
