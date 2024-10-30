<?php
return CRM_Core_CodeGen_OptionGroup::create('participant_role', 'a/0014')
  ->addMetadata([
    'title' => ts('Participant Role'),
    'description' => ts('Define participant roles for events here (e.g. Attendee, Host, Speaker...). You can then assign roles and search for participants by role.'),
    'data_type' => 'Integer',
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Attendee'), 'Attendee', 1],
    [ts('Volunteer'), 'Volunteer', 2],
    [ts('Host'), 'Host', 3],
    [ts('Speaker'), 'Speaker', 4],
  ])
  ->addDefaults([
    'filter' => 1,
  ]);
