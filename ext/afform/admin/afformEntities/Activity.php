<?php
return [
  'type' => 'primary',
  'defaults' => "{
    data: {
      source_contact_id: 'user_contact_id',
      activity_type_id: ''
    }
  }",
  'boilerplate' => [
    ['#tag' => 'af-field', 'name' => 'subject'],
  ],
];
