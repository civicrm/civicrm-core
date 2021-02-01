<?php
return [
  'entity' => 'Contact',
  'contact_type' => 'Individual',
  'defaults' => "{
    data: {
      contact_type: 'Individual',
      source: afform.title
    },
    'url-autofill': '1'
  }",
  'icon' => 'fa-user',
  'boilerplate' => [
    ['#tag' => 'afblock-name-individual'],
  ],
];
