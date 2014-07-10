{php}
  $dir = SMARTY_DIR . '/../../CRM/Upgrade/4.5.beta2.msg_template/message_templates';
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
