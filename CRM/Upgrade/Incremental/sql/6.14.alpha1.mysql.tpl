{* file to handle db changes in 6.14.alpha1 during upgrade *}

-- Should never come up but in the php file we change the column to not null, so this will avoid a crash just in case.
UPDATE civicrm_word_replacement SET find_word = '' WHERE find_word IS NULL;
UPDATE civicrm_word_replacement SET replace_word = '' WHERE replace_word IS NULL;
