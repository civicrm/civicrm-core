<?php
return CRM_Core_CodeGen_OptionGroup::create('participant_listing', 'a/0027')
  ->addMetadata([
    'title' => ts('Participant Listing'),
  ])
  ->addValues(['label', 'name', 'value', 'description'], [
    [ts('Name Only'), 'Name Only', 1, ts('CRM_Event_Page_ParticipantListing_Name'), 'is_reserved' => 1],
    [ts('Name and Email'), 'Name and Email', 2, ts('CRM_Event_Page_ParticipantListing_NameAndEmail'), 'is_reserved' => 1],
    [ts('Name, Status and Register Date'), 'Name, Status and Register Date', 3, ts('CRM_Event_Page_ParticipantListing_NameStatusAndDate'), 'is_reserved' => 1],
  ]);
