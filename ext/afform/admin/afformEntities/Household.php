<?php
return [
  'entity' => 'Contact',
  'contact_type' => 'Household',
  'defaults' => "{
    data: {
      contact_type: 'Household',
      source: afform.title
    },
    'url-autofill': '1'
  }",
  'icon' => 'fa-home',
  'boilerplate' => [
    ['#tag' => 'afblock-name-household'],
  ],
];
