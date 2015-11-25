{php}
  $dir = SMARTY_DIR . '/../../CRM/Upgrade/4.7.beta1.msg_template/message_templates';
  $templates = array();
  $ovNames = array(
    'event' => array(
      'participant_transferred' => ts('Events - Registration Transferred Notice',             array('escape' => 'sql')),
    ),
  );

  $this->assign('ovNames', $ovNames);
  $this->assign('dir', $dir);

  foreach (preg_grep('/\.tpl$/', scandir($dir)) as $filename) {
    $parts = explode('_', basename($filename, '.tpl'));
    $templates[] = array('type' => array_pop($parts), 'name' => implode('_', $parts), 'filename' => "$dir/$filename");
  }
  $this->assign('templates', $templates);
{/php}
{foreach from=$ovNames key=name item=ignore}
  SELECT @tpl_ogid_{$name} := MAX(id) FROM civicrm_option_group WHERE name = 'msg_tpl_workflow_{$name}';
  SELECT @max_val    := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @tpl_ogid_{$name};
  SELECT @max_wt     := max(weight) from civicrm_option_value where option_group_id=@tpl_ogid_{$name};
{/foreach}

INSERT INTO civicrm_option_value
  (option_group_id,        name,       {localize field='label'}label{/localize},   value,                                  weight) VALUES
{foreach from=$ovNames key=gName item=ovs name=for_groups}
{foreach from=$ovs key=vName item=label name=for_values}
      (@tpl_ogid_{$gName}, '{$vName}', {localize}'{$label}'{/localize}, (SELECT @max_val := @max_val+1), (SELECT @max_wt := @max_wt+1)) {if $smarty.foreach.for_groups.last and $smarty.foreach.for_values.last};{else},{/if}
{/foreach}
{/foreach}

{foreach from=$ovNames key=gName item=ovs}
{foreach from=$ovs key=vName item=label}
    SELECT @tpl_ovid_{$vName} := MAX(id) FROM civicrm_option_value WHERE option_group_id = @tpl_ogid_{$gName} AND name = '{$vName}';
{/foreach}
{/foreach}

INSERT INTO civicrm_msg_template
  (msg_title,      msg_subject,                  msg_text,                  msg_html,                  workflow_id,        is_default, is_reserved) VALUES
{foreach from=$ovNames key=gName item=ovs name=for_groups}
{foreach from=$ovs key=vName item=title name=for_values}
      {fetch assign=subject file="`$smarty.const.SMARTY_DIR`/../../CRM/Upgrade/4.7.beta1.msg_template/message_templates/`$vName`_subject.tpl"}
      {fetch assign=text    file="`$smarty.const.SMARTY_DIR`/../../CRM/Upgrade/4.7.beta1.msg_template/message_templates/`$vName`_text.tpl"}
      {fetch assign=html    file="`$smarty.const.SMARTY_DIR`/../../CRM/Upgrade/4.7.beta1.msg_template/message_templates/`$vName`_html.tpl"}
      ('{$title}', '{$subject|escape:"quotes"}', '{$text|escape:"quotes"}', '{$html|escape:"quotes"}', @tpl_ovid_{$vName}, 1,          0),
      ('{$title}', '{$subject|escape:"quotes"}', '{$text|escape:"quotes"}', '{$html|escape:"quotes"}', @tpl_ovid_{$vName}, 0,          1) {if $smarty.foreach.for_groups.last and $smarty.foreach.for_values.last};{else},{/if}
{/foreach}
{/foreach}

{foreach from=$templates item=tpl}
  {fetch assign=content file=$tpl.filename}
  SELECT @workflow_id := MAX(id) FROM civicrm_option_value WHERE name = '{$tpl.name}';
  SELECT @content := msg_{$tpl.type} FROM civicrm_msg_template WHERE workflow_id = @workflow_id AND is_reserved = 1 LIMIT 1;
  UPDATE civicrm_msg_template SET msg_{$tpl.type} = '{$content|escape:"quotes"}' WHERE workflow_id = @workflow_id AND (is_reserved = 1 OR (is_default = 1 AND msg_{$tpl.type} = @content));
{/foreach}
