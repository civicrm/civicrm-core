<?php

return [
  'name' => 'EventCartParticipant',
  'table' => 'civicrm_event_cart_participant',
  'class' => 'CRM_Event_Cart_DAO_EventCartParticipant',
  'getInfo' => fn() => [
    'title' => ts('Event Cart Participant'),
    'title_plural' => ts('Event Cart Participants'),
    'description' => ts('Event Cart Participant'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Event Cart Participant ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Event Cart Participant ID'),
      'add' => '5.76',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'participant_id' => [
      'title' => ts('Participant ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Participant ID'),
      'add' => '5.76',
      'input_attrs' => [
        'label' => ts('Participant'),
      ],
      'entity_reference' => [
        'entity' => 'Participant',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'cart_id' => [
      'title' => ts('Event Cart ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Event Cart ID'),
      'add' => '5.67',
      'input_attrs' => [
        'label' => ts('Event Cart'),
      ],
      'entity_reference' => [
        'entity' => 'Cart',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
