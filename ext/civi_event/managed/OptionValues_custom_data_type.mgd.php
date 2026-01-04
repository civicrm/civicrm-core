<?php
use CRM_Event_ExtensionUtil as E;

return [
  [
    'name' => 'OptionGroup_custom_data_type_OptionValue_ParticipantRole',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'custom_data_type',
        'label' => E::ts('Role'),
        'value' => 1,
        'name' => 'ParticipantRole',
        'grouping' => 'role_id',
        'is_reserved' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_custom_data_type_OptionValue_ParticipantEventName',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'custom_data_type',
        'label' => E::ts('Event Name'),
        'value' => 2,
        'name' => 'ParticipantEventName',
        'grouping' => 'event_id',
        'is_reserved' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_custom_data_type_OptionValue_ParticipantEventType',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'custom_data_type',
        'label' => E::ts('Event Type'),
        'value' => 3,
        'name' => 'ParticipantEventType',
        'grouping' => 'event_id.event_type_id',
        'is_reserved' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
];
