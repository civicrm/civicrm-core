<?php
return [
  'type' => 'primary',
  'defaults' => "{
    security: 'FBAC'
  }",
  'icon' => 'fa-handshake-o',
  'boilerplate' => FALSE,
  'repeatable' => FALSE,
  'alterFields' => [
    'contact_id_a' => ['input_attrs' => ['multiple' => TRUE]],
    'contact_id_b' => ['input_attrs' => ['multiple' => TRUE]],
  ],
];
