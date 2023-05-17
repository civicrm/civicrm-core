<?php

// dev/core#3783 Recent Items providers
return CRM_Core_CodeGen_OptionGroup::create('recent_items_providers', 'b/99')
  ->addMetadata([
    'title' => ts('Recent Items Providers'),
  ])
  ->addValueTable(['label', 'value', 'name'], [
    [ts('Contacts'), 'Contact', 'Contact'],
    [ts('Relationships'), 'Relationship', 'Relationship'],
    [ts('Activities'), 'Activity', 'Activity'],
    [ts('Notes'), 'Note', 'Note'],
    [ts('Groups'), 'Group', 'Group'],
    [ts('Cases'), 'Case', 'Case'],
    [ts('Contributions'), 'Contribution', 'Contribution'],
    [ts('Participants'), 'Participant', 'Participant'],
    [ts('Memberships'), 'Membership', 'Membership'],
    [ts('Pledges'), 'Pledge', 'Pledge'],
    [ts('Events'), 'Event', 'Event'],
    [ts('Campaigns'), 'Campaign', 'Campaign'],
  ])
  ->addDefaults([
    'description' => '',
    'filter' => NULL,
    'weight' => 1,
    // Why do these all have the same weight? Shrug.
    'is_reserved' => 1,
  ]);
