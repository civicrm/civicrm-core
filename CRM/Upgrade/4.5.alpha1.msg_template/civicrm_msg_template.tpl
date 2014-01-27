{php}
  $dir = SMARTY_DIR . '/../../CRM/Upgrade/4.5.alpha1.msg_template/message_templates';

  $ovNames = array(
    'contribution' => array(
      'payment_or_refund_notification' => ts('Additional Payment Receipt or Refund Notification',             array('escape' => 'sql')),
    ),
  );

  $this->assign('ovNames', $ovNames);
  $this->assign('dir', $dir);
{/php}

{foreach from=$ovNames key=name item=ignore}
  SELECT @tpl_ogid_{$name} := MAX(id) FROM civicrm_option_group WHERE name = 'msg_tpl_workflow_{$name}';
{/foreach}

INSERT INTO civicrm_option_value
  (option_group_id,        name,       {localize field='label'}label{/localize},   value,                                  weight) VALUES
{foreach from=$ovNames key=gName item=ovs name=for_groups}
{foreach from=$ovs key=vName item=label name=for_values}
      (@tpl_ogid_{$gName}, '{$vName}', {localize}'{$label}'{/localize},            {$smarty.foreach.for_values.iteration}, {$smarty.foreach.for_values.iteration}) {if $smarty.foreach.for_groups.last and $smarty.foreach.for_values.last};{else},{/if}
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
      {fetch assign=subject file="`$smarty.const.SMARTY_DIR`/../../CRM/Upgrade/4.5.alpha1.msg_template/message_templates/`$vName`_subject.tpl"}
      {fetch assign=text    file="`$smarty.const.SMARTY_DIR`/../../CRM/Upgrade/4.5.alpha1.msg_template/message_templates/`$vName`_text.tpl"}
      {fetch assign=html    file="`$smarty.const.SMARTY_DIR`/../../CRM/Upgrade/4.5.alpha1.msg_template/message_templates/`$vName`_html.tpl"}
      ('{$title}', '{$subject|escape:"quotes"}', '{$text|escape:"quotes"}', '{$html|escape:"quotes"}', @tpl_ovid_{$vName}, 1,          0),
      ('{$title}', '{$subject|escape:"quotes"}', '{$text|escape:"quotes"}', '{$html|escape:"quotes"}', @tpl_ovid_{$vName}, 0,          1) {if $smarty.foreach.for_groups.last and $smarty.foreach.for_values.last};{else},{/if}
{/foreach}
{/foreach}
