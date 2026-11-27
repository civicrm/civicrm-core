<?php
return [
  'type' => 'primary',
  'defaults' => "{
    actions: {create: true, update: true}
  }",
  'boilerplate' => [
    ['#tag' => 'af-field', 'name' => 'title'],
  ],
  'alterFields' => [
    // Machine name is generated from the title on create
    'name' => FALSE,
  ],
];
