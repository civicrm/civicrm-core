<?php
return CRM_Core_CodeGen_OptionGroup::create('event_badge', 'a/0049')
  ->addMetadata([
    'title' => ts('Event Name Badge'),
  ])
  ->addValues(['label', 'name', 'value', 'description'], [
    [ts('Name Only'), 'CRM_Event_Badge_Simple', 1, ts('Simple Event Name Badge'), 'is_reserved' => 1],
    [ts('Name Tent'), 'CRM_Event_Badge_NameTent', 2, ts('Name Tent'), 'is_reserved' => 1],
    [ts('With Logo'), 'CRM_Event_Badge_Logo', 3, ts('You can set your own background image'), 'is_reserved' => 1],
    [ts('5395 with Logo'), 'CRM_Event_Badge_Logo5395', 4, ts('Avery 5395 compatible labels with logo (4 up by 2, 59.2mm x 85.7mm)'), 'is_reserved' => 1],
  ]);
