<?php
return CRM_Core_CodeGen_OptionGroup::create('event_contacts', 'a/0062')
  ->addMetadata([
    'title' => ts('Event Recipients'),
  ])
  ->addValues(['label', 'name', 'value'], [
    [ts('Participant Role'), 'participant_role', 1],
  ]);
