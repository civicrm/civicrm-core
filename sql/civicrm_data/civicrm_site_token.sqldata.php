<?php

return CRM_Core_CodeGen_SqlData::create('civicrm_site_token')
  ->addValues([
    [
      'label' => ts('Message Header'),
      'name' => 'message_header',
      'body_html' => '<div></div>',
      'body_text' => ts('Sample Header for TEXT formatted content.'),
      'is_reserved' => 1,
    ],
  ])
  ->addDefaults([
    'is_active' => 1,
    'domain_id' => 1,
  ]);
