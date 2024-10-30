<?php
return CRM_Core_CodeGen_OptionGroup::create('participant_listing', 'a/0027')
  ->addMetadata([
    'title' => ts('Participant Listing'),
  ])
  ->addValues([
    [
      'label' => ts('Name Only'),
      'value' => 1,
      'name' => 'Name Only',
      'description' => ts('CRM_Event_Page_ParticipantListing_Name'),
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Name and Email'),
      'value' => 2,
      'name' => 'Name and Email',
      'description' => ts('CRM_Event_Page_ParticipantListing_NameAndEmail'),
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Name, Status and Register Date'),
      'value' => 3,
      'name' => 'Name, Status and Register Date',
      'description' => ts('CRM_Event_Page_ParticipantListing_NameStatusAndDate'),
      'is_reserved' => 1,
    ],
  ]);
