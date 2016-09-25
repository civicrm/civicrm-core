{php}
  $dir = SMARTY_DIR . '/../../CRM/Upgrade/4.4.alpha1.msg_template/message_templates';
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
