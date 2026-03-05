<?php
return [
  'type' => 'primary',
  'defaults' => "{
    data: {
      contact_id: 'user_contact_id',
      financial_type_id: 1
    }
  }",
  'boilerplate' => [
    [
      '#tag' => 'af-field',
      'name' => 'source',
    ],
  ],
];
