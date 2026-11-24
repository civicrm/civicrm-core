<?php

return [
  [
    'name' => 'OptionGroup_event_calendar_link_provider',
    'entity' => 'OptionGroup',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'event_calendar_link_provider',
        'title' => ts('Event Calendar Link Providers'),
        'description' => ts('Calendar links available for events'),
        'data_type' => 'String',
        'is_reserved' => FALSE,
        'option_value_fields' => ['name', 'label', 'description'],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'OptionGroup_event_calendar_link_provider_OptionValue_iCalendar',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'event_calendar_link_provider',
        'label' => ts('iCalendar download'),
        'value' => 'ical',
        'name' => 'iCalendar',
      ],
      'match' => ['option_group_id', 'name', 'value'],
    ],
  ],
  [
    'name' => 'OptionGroup_event_calendar_link_provider_OptionValue_Google',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'event_calendar_link_provider',
        'label' => ts('Google Calendar'),
        'value' => 'google',
        'name' => 'Google',
      ],
      'match' => ['option_group_id', 'name', 'value'],
    ],
  ],
];
