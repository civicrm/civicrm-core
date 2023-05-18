<?php
return CRM_Core_CodeGen_OptionGroup::create('note_used_for', 'a/0047')
  ->addMetadata([
    'title' => ts('Note Used For'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Contacts'), 'Contact', 'civicrm_contact'],
    [ts('Relationships'), 'Relationship', 'civicrm_relationship'],
    [ts('Participants'), 'Participant', 'civicrm_participant'],
    [ts('Contributions'), 'Contribution', 'civicrm_contribution'],
  ]);
