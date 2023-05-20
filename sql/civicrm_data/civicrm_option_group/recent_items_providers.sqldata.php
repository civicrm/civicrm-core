<?php

// dev/core#3783 Recent Items providers
return CRM_Core_CodeGen_OptionGroup::create('recent_items_providers', 'b/99')
  ->addMetadata([
    'title' => ts('Recent Items Providers'),
  ])
  ->addValueTable(['label', 'name'], [
    [ts('Contacts'), 'Contact'],
    [ts('Relationships'), 'Relationship'],
    [ts('Activities'), 'Activity'],
    [ts('Notes'), 'Note'],
    [ts('Groups'), 'Group'],
    [ts('Cases'), 'Case'],
    [ts('Contributions'), 'Contribution'],
    [ts('Participants'), 'Participant'],
    [ts('Memberships'), 'Membership'],
    [ts('Pledges'), 'Pledge'],
    [ts('Events'), 'Event'],
    [ts('Campaigns'), 'Campaign'],
  ])
  ->addDefaults([
    'description' => '',
    'filter' => NULL,
    'weight' => 1,
    // Why do these all have the same weight? Shrug.
    'is_reserved' => 1,
  ])
  ->syncColumns('fill', ['name' => 'value']);
