<?php
$multiClient = \Civi::settings()->get('civicaseAllowMultipleClients');
// Format contact_id as an array if multivalued
$default = $multiClient ? "['user_contact_id']" : "'user_contact_id'";
// phpcs:disable
return [
  'type' => 'primary',
  'defaults' => "{
    data: {
      contact_id: $default,
      case_type_id: ''
    },
    actions: {create: true, update: false}
  }",
  'boilerplate' => [
    ['#tag' => 'af-field', 'name' => 'subject'],
  ],
];
