<?php
return CRM_Core_CodeGen_OptionGroup::create('event_badge', 'a/0049')
  ->addMetadata([
    'title' => ts('Event Name Badge'),
  ])
  ->addValues([
    [
      'label' => ts('Name Only'),
      'value' => 1,
      'name' => 'CRM_Event_Badge_Simple',
      'description' => ts('Simple Event Name Badge'),
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Name Tent'),
      'value' => 2,
      'name' => 'CRM_Event_Badge_NameTent',
      'description' => ts('Name Tent'),
      'is_reserved' => 1,
    ],
    [
      'label' => ts('With Logo'),
      'value' => 3,
      'name' => 'CRM_Event_Badge_Logo',
      'description' => ts('You can set your own background image'),
      'is_reserved' => 1,
    ],
    [
      'label' => ts('5395 with Logo'),
      'value' => 4,
      'name' => 'CRM_Event_Badge_Logo5395',
      'description' => ts('Avery 5395 compatible labels with logo (4 up by 2, 59.2mm x 85.7mm)'),
      'is_reserved' => 1,
    ],
  ]);
