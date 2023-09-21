<?php
return CRM_Core_CodeGen_OptionGroup::create('custom_data_type', 'a/0034')
  ->addMetadata([
    'title' => ts('Custom Data Type'),
  ])
  // Note: When adding options to this group, the 'name' *must* begin with the exact name of the base entity,
  // as that's the (very lo-tech) way these options are matched with their base entity.
  // Wrong: 'name' => 'ActivitiesByStatus'
  // Right: 'name' => 'ActivityByStatus'
  ->addValues([
    [
      'label' => ts('Role'),
      'value' => 1,
      'name' => 'ParticipantRole',
      'grouping' => 'role_id',
    ],
    [
      'label' => ts('Event Name'),
      'value' => 2,
      'name' => 'ParticipantEventName',
      'grouping' => 'event_id',
    ],
    [
      'label' => ts('Event Type'),
      'value' => 3,
      'name' => 'ParticipantEventType',
      'grouping' => 'event_id.event_type_id',
    ],
  ]);
