{* file to handle db changes in 5.70.alpha1 during upgrade *}

-- Add name field, make frontend_title required (in conjunction with php function)
{if $multilingual}
    {foreach from=$locales item=locale}
      UPDATE `civicrm_membership_type`
      SET `frontend_title_{$locale}` = `name_{$locale}`, `title_{$locale}` = `name_{$locale}`;
      UPDATE `civicrm_membership_type` m1 INNER JOIN `civicrm_membership_type` m2
      ON m1.`name_{$locale}` = m2.`name_{$locale}` AND m2.id < m1.id
      SET m1.`name_{$locale}` = CONCAT(m1.`name_{$locale}`, m1.id)

    {/foreach}
{else}
  UPDATE `civicrm_membership_type`
  SET `frontend_title` = `name`, 'title` = `name`;

  UPDATE `civicrm_membership_type` m1 INNER JOIN `civicrm_membership_type` m2
  ON m1.`name` = m2.`name` AND m2.id < m1.id
  SET m1.`name` = CONCAT(m1.`name`, m1.id)
{/if}
