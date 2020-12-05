{* file to handle db changes in 5.33.beta2 during upgrade *}

{* dev/core#2188, dev/core#337 (redux) - Ranges aren't support on these widgets. Note: This same change ran previously in 5.9.beta and contemporaneously in 5.32.1, and that's OK. *}
UPDATE civicrm_custom_field SET is_search_range = 0 WHERE html_type IN ('Radio', 'Select') AND data_type IN ('Money', 'Float', 'Int');
