<?php
return [
  'type' => 'primary',
  'defaults' => "{
    data: {
      contact_type: 'Organization',
      source: afform.title
    }
  }",
  'icon' => 'fa-building',
  'boilerplate' => [
    ['#tag' => 'afblock-name-organization'],
  ],
];
