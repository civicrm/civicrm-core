{php}
  $dir = SMARTY_DIR . '/../../CRM/Upgrade/4.5.beta2.msg_template/message_templates/sample';
  $templates = array();
  foreach (preg_grep('/\.tpl$/', scandir($dir)) as $filename) {
    $templates[] = array('name' => basename($filename, '.tpl'), 'filename' => "$dir/$filename");
  }
  $this->assign('templates', $templates);
{/php}

{foreach from=$templates item=tpl}
  {fetch assign=content file=$tpl.filename}
INSERT INTO civicrm_msg_template
   (msg_title,  msg_subject, msg_text, msg_html, workflow_id, is_default, is_reserved) VALUES
    ('{$tpl.name} Template', '{$tpl.name}', '', '{$content|escape:"quotes"}' ,NULL, 1, 0);
{/foreach}

{php}
  $dir = SMARTY_DIR . '/../../CRM/Upgrade/4.5.beta2.msg_template/message_templates';
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
