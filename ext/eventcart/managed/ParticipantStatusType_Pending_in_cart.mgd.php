<?php
use CRM_Event_Cart_ExtensionUtil as E;

return [
  [
    'name' => 'ParticipantStatusType_Pending_in_cart',
    'entity' => 'ParticipantStatusType',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Pending in cart',
        'label' => E::ts('Pending in cart'),
        'class' => 'Pending',
        'is_reserved' => TRUE,
        'visibility_id:name' => 'admin',
      ],
      'match' => [
        'name',
      ],
    ],
  ],
];
