<?php

return [
  'name' => 'ParticipantPayment',
  'table' => 'civicrm_participant_payment',
  'class' => 'CRM_Event_DAO_ParticipantPayment',
  'getInfo' => fn() => [
    'title' => ts('Participant Payment'),
    'title_plural' => ts('Participant Payments'),
    'description' => ts('Participant payments table (deprecated - use lineitems)'),
    'log' => TRUE,
    'add' => '1.7',
  ],
  'getIndices' => fn() => [
    'UI_contribution_participant' => [
      'fields' => [
        'contribution_id' => TRUE,
        'participant_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '2.0',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Payment ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Participant Payment ID'),
      'add' => '1.7',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'participant_id' => [
      'title' => ts('Participant ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Participant ID (FK)'),
      'add' => '1.7',
      'input_attrs' => [
        'label' => ts('Participant'),
      ],
      'entity_reference' => [
        'entity' => 'Participant',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'contribution_id' => [
      'title' => ts('Contribution ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to contribution table.'),
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Contribution'),
      ],
      'entity_reference' => [
        'entity' => 'Contribution',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
