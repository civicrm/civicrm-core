{* file to handle db changes in 5.71.alpha1 during upgrade *}

-- Add name field, make frontend_title required (in conjunction with php function)
{localize field='title,frontend_title'}
  UPDATE `civicrm_uf_group`
  SET `frontend_title` = `title`
  WHERE `frontend_title` IS NULL OR `frontend_title` = '';

  UPDATE `civicrm_uf_group`
  SET `name` = CONCAT(`title`, `id`)
  WHERE `name` IS NULL OR `name` = '';
{/localize}
