<?php
return CRM_Core_CodeGen_OptionGroup::create('custom_data_type', 'a/0034')
  ->addMetadata([
    'title' => ts('Custom Data Type'),
  ])
  ->addValues(['label', 'name', 'value'], [
    [ts('Participants (Role)'), 'ParticipantRole', 1, 'grouping' => 'role_id'],
    [ts('Participants (Event Name)'), 'ParticipantEventName', 2, 'grouping' => 'event_id'],
    [ts('Participants (Event Type)'), 'ParticipantEventType', 3, 'grouping' => 'event_id.event_type_id'],
  ]);
