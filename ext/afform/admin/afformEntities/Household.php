<?php
return [
  'type' => 'primary',
  'defaults' => "{
    data: {
      contact_type: 'Household',
      source: afform.title
    }
  }",
  'icon' => 'fa-home',
  'boilerplate' => [
    ['#tag' => 'afblock-name-household'],
  ],
];
