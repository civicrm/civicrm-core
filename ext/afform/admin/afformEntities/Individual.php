<?php
return [
  'type' => 'primary',
  'defaults' => "{
    data: {
      source: afform.title
    }
  }",
  'icon' => 'fa-user',
  'boilerplate' => [
    ['#tag' => 'af-field', 'name' => 'first_name'],
    ['#tag' => 'af-field', 'name' => 'last_name'],
  ],
];
