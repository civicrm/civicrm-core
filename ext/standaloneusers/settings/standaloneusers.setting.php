<?php

return [
  'standaloneusers_session_max_lifetime' => [
    'name'        => 'standaloneusers_session_max_lifetime',
    'type'        => 'Integer',
    'title'       => ts('Maxiumum Session Lifetime'),
    'description' => ts('Duration (in seconds) until a user session expires'),
    // 24 days (= Drupal default)
    'default'     => 24 * 24 * 60 * 60,
    'html_type'   => 'text',
    'is_domain'   => 1,
    'is_contact'  => 0,
  ],
];
