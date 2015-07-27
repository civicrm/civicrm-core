-- CRM-7817
{if $addPetitionOptionGroup}

INSERT INTO `civicrm_option_group`
    ( `name`, {localize field='label'}label{/localize}, {localize field='description'}description{/localize}, `is_reserved`, `is_active` )
VALUES
     ( 'msg_tpl_workflow_petition', {localize}'{ts escape="sql"}Message Template Workflow for Petition{/ts}'{/localize},{localize}'{ts escape="sql"}Message Template Workflow for Petition{/ts}'{/localize}, 0, 1 );

SELECT @option_group_id := MAX(id) from civicrm_option_group WHERE name = 'msg_tpl_workflow_petition';

INSERT INTO `civicrm_option_value`
 ( `option_group_id`, {localize field='label'}label{/localize}, `name`, `value`, `weight`, `is_active` )
VALUES
 ( @option_group_id, {localize}'{ts escape="sql"}Petition - signature added{/ts}'{/localize}, 'petition_sign', 1, 1, 1 ),
 ( @option_group_id, {localize}'{ts escape="sql"}Petition - need verification{/ts}'{/localize}, 'petition_confirmation_needed', 2, 2, 1 );

SELECT @tpl_ovid_petition_sign := MAX(id) FROM civicrm_option_value WHERE option_group_id = @option_group_id AND name = 'petition_sign';
SELECT @tpl_ovid_petition_confirmation_needed := MAX(id) FROM civicrm_option_value WHERE option_group_id = @option_group_id AND name = 'petition_confirmation_needed';

-- get the petition sign template values.
{fetch assign=subject_petition_sign file="`$smarty.const.SMARTY_DIR`/../../CRM/Upgrade/3.4.beta2.msg_template/message_templates/petition_sign_subject.tpl"}
{fetch assign=text_petition_sign  file="`$smarty.const.SMARTY_DIR`/../../CRM/Upgrade/3.4.beta2.msg_template/message_templates/petition_sign_text.tpl"}
{fetch assign=html_petition_sign  file="`$smarty.const.SMARTY_DIR`/../../CRM/Upgrade/3.4.beta2.msg_template/message_templates/petition_sign_html.tpl"}

-- get the petition confirmation needed template values.
{fetch assign=subject_petition_confirmation_needed file="`$smarty.const.SMARTY_DIR`/../../CRM/Upgrade/3.4.beta2.msg_template/message_templates/petition_confirmation_needed_subject.tpl"}
{fetch assign=text_petition_confirmation_needed  file="`$smarty.const.SMARTY_DIR`/../../CRM/Upgrade/3.4.beta2.msg_template/message_templates/petition_confirmation_needed_text.tpl"}
{fetch assign=html_petition_confirmation_needed  file="`$smarty.const.SMARTY_DIR`/../../CRM/Upgrade/3.4.beta2.msg_template/message_templates/petition_confirmation_needed_html.tpl"}

INSERT INTO civicrm_msg_template
    (msg_title, msg_subject, msg_text, msg_html, workflow_id, is_default, is_reserved)
    VALUES
    ( '{ts escape="sql"}Petition - signature added{/ts}', '{$subject_petition_sign|escape:"quotes"}', '{$text_petition_sign|escape:"quotes"}', '{$html_petition_sign|escape:"quotes"}', @tpl_ovid_petition_sign, 1, 0),

    ('{ts escape="sql"}Petition - signature added{/ts}', '{$subject_petition_sign|escape:"quotes"}', '{$text_petition_sign|escape:"quotes"}', '{$html_petition_sign|escape:"quotes"}', @tpl_ovid_petition_sign, 0, 1),

    ('{ts escape="sql"}Petition - need verification{/ts}', '{$subject_petition_confirmation_needed|escape:"quotes"}', '{$text_petition_confirmation_needed|escape:"quotes"}', '{$html_petition_confirmation_needed|escape:"quotes"}', @tpl_ovid_petition_confirmation_needed, 1, 0),

    ('{ts escape="sql"}Petition - need verification{/ts}', '{$subject_petition_confirmation_needed|escape:"quotes"}', '{$text_petition_confirmation_needed|escape:"quotes"}', '{$html_petition_confirmation_needed|escape:"quotes"}', @tpl_ovid_petition_confirmation_needed, 0, 1);

{/if}

-- CRM-7834
{php}
  $dir = SMARTY_DIR . '/../../CRM/Upgrade/3.4.beta2.msg_template/message_templates';
  $templates = array();
  foreach (preg_grep('/\.tpl$/', scandir($dir)) as $filename) {
    $parts = explode('_', basename($filename, '.tpl'));
    $templates[] = array('type' => array_pop($parts), 'name' => implode('_', $parts), 'filename' => "$dir/$filename");
  }
  $this->assign('templates', $templates);
{/php}

{foreach from=$templates item=tpl}
  {fetch assign=content file=$tpl.filename}
  SELECT @workflow_id := MAX(id) FROM civicrm_option_value WHERE name = '{$tpl.name}';
  SELECT @content := msg_{$tpl.type} FROM civicrm_msg_template WHERE workflow_id = @workflow_id AND is_reserved = 1 LIMIT 1;
  UPDATE civicrm_msg_template SET msg_{$tpl.type} = '{$content|escape:"quotes"}' WHERE workflow_id = @workflow_id AND (is_reserved = 1 OR (is_default = 1 AND msg_{$tpl.type} = @content));
{/foreach}