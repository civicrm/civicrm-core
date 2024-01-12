<?php
return [
  'type' => 'primary',
  'defaults' => "{
    data: {
      contact_type: 'Individual',
      source: afform.title
    }
  }",
  'icon' => 'fa-user',
  'boilerplate' => [
    ['#tag' => 'afblock-name-individual'],
  ],
];
