-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC. All rights reserved.                        |
-- |                                                                    |
-- | This work is published under the GNU AGPLv3 license with some      |
-- | permitted exceptions and without any warranty. For full license    |
-- | and copyright information, see https://civicrm.org/licensing       |
-- +--------------------------------------------------------------------+
--
-- Generated from {$smarty.template}
-- {$generated}
--

{foreach from=$optionGroupNames key=name item=description}
INSERT INTO civicrm_option_group
  (name, {localize field='title'}title{/localize}, {localize field='description'}description{/localize}, is_reserved, is_active)
  VALUES
  ('msg_tpl_workflow_{$name}', {localize}'{$description}'{/localize}, {localize}'{$description}'{/localize}, 1,  1);
{/foreach}

{foreach from=$templates key=templateName item=template}
  {if $template.option_group_name}
    INSERT INTO civicrm_option_value
      (option_group_id, name,  {localize field='label'}label{/localize}, value, weight)
    VALUES
      ((SELECT MAX(id) FROM civicrm_option_group WHERE name = 'msg_tpl_workflow_{$template.option_group_name}') , '{$templateName}', {localize}'{$template.title}'{/localize}, {$template.value}, {$template.weight});

    INSERT INTO civicrm_msg_template
    (msg_title, msg_subject, msg_text, msg_html, workflow_name, workflow_id, is_default, is_reserved)
    VALUES
    ('{$template.title}', '{$template.subject|escape:"quotes"}', '', '{$template.msg_html|escape:"quotes"}', '{$template.name}', (SELECT id FROM civicrm_option_value WHERE name = '{$template.name}'), 1, 0);

    INSERT INTO civicrm_msg_template
    (msg_title, msg_subject, msg_text, msg_html, workflow_name, workflow_id, is_default, is_reserved)
    VALUES
    ('{$template.title}', '{$template.subject|escape:"quotes"}', '', '{$template.msg_html|escape:"quotes"}', '{$template.name}', (SELECT id FROM civicrm_option_value WHERE name = '{$template.name}'), 0, 1);
  {else}
    INSERT INTO civicrm_msg_template
    (msg_text, msg_title, msg_subject, msg_html)
    VALUES
    ('', '{$template.title}', '{$template.subject|escape:"quotes"}', '{$template.msg_html|escape:"quotes"}');
  {/if}
{/foreach}
