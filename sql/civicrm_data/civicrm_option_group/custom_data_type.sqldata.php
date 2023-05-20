<?php
return CRM_Core_CodeGen_OptionGroup::create('custom_data_type', 'a/0034')
  ->addMetadata([
    'title' => ts('Custom Data Type'),
  ])
  ->addValues([
    [
      'label' => ts('Participants (Role)'),
      'value' => 1,
      'name' => 'ParticipantRole',
      'grouping' => 'role_id',
    ],
    [
      'label' => ts('Participants (Event Name)'),
      'value' => 2,
      'name' => 'ParticipantEventName',
      'grouping' => 'event_id',
    ],
    [
      'label' => ts('Participants (Event Type)'),
      'value' => 3,
      'name' => 'ParticipantEventType',
      'grouping' => 'event_id.event_type_id',
    ],
  ]);
