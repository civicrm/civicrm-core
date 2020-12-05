{* file to handle db changes in 5.32.1 during upgrade *}

{* dev/core#2188, dev/core#337 (redux) - Ranges aren't support on these widgets. Note: This same change will run again 5.33.beta, and that's OK. *}
UPDATE civicrm_custom_field SET is_search_range = 0 WHERE html_type IN ('Radio', 'Select') AND data_type IN ('Money', 'Float', 'Int');
