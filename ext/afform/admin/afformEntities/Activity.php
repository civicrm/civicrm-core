<?php
return [
  'defaults' => "{
    data: {
      source_contact_id: 'user_contact_id',
      activity_type_id: ''
    },
    'url-autofill': '1'
  }",
  'boilerplate' => [
    ['#tag' => 'af-field', 'name' => 'subject'],
  ],
];
