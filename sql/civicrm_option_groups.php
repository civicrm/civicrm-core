<?php

return [
  // Encounter Medium Option Values (for case activities)
  'encounter_medium' => CRM_Core_CodeGen_OptionGroup::create('encounter_medium')
    ->addMetadata([
      // Shouldn't these be translated?
      'title' => 'Encounter Medium',
      'description' => 'Encounter medium for case activities (e.g. In Person, By Phone, etc.)',
    ])
    ->addValues(['label', 'name'], [
      [ts('In Person'), 'in_person'],
      [ts('Phone'), 'phone', 'is_default' => 1],
      [ts('Email'), 'email'],
      [ts('Fax'), 'fax'],
      [ts('Letter Mail'), 'letter_mail'],
    ])
    ->addDefaults([
      'is_reserved' => 1,
    ]),

  // CRM-13833
  'soft_credit_type' => CRM_Core_CodeGen_OptionGroup::create('soft_credit_type')
    ->addMetadata([
      'title' => ts('Soft Credit Types'),
    ])
    ->addValues(['label', 'value', 'name'], [
      [ts('In Honor of'), 1, 'in_honor_of', 'is_reserved' => 1],
      [ts('In Memory of'), 2, 'in_memory_of', 'is_reserved' => 1],
      [ts('Solicited'), 3, 'solicited', 'is_reserved' => 1, 'is_default' => 1],
      [ts('Household'), 4, 'household'],
      [ts('Workplace Giving'), 5, 'workplace'],
      [ts('Foundation Affiliate'), 6, 'foundation_affiliate'],
      [ts('3rd-party Service'), 7, '3rd-party_service'],
      [ts('Donor-advised Fund'), 8, 'donor-advised_fund'],
      [ts('Matched Gift'), 9, 'matched_gift'],
      [ts('Personal Campaign Page'), 10, 'pcp', 'is_reserved' => 1],
      [ts('Gift'), 11, 'gift', 'is_reserved' => 1],
    ])
    ->addDefaults([]),

  // dev/core#3783 Recent Items providers
  'recent_items_providers' => CRM_Core_CodeGen_OptionGroup::create('recent_items_providers')
    ->addMetadata([
      'title' => ts('Recent Items Providers'),
    ])
    ->addValues(['label', 'value', 'name'], [
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
    ]),
];
