{* file to handle db changes in 5.9.beta1 during upgrade *}

{* dev/core#337 Set search by range to be false which was the effect of the original implementation of any Money, Float, Int fields where the widget was Radio or Select *}
UPDATE civicrm_custom_field SET is_search_range = 0 WHERE html_type IN ('Radio', 'Select') AND data_type IN ('Money', 'Float', 'Int');
