<?php

return [
  'name' => 'MailingEventTrackableURLOpen',
  'table' => 'civicrm_mailing_event_trackable_url_open',
  'class' => 'CRM_Mailing_Event_DAO_MailingEventTrackableURLOpen',
  'getInfo' => fn() => [
    'title' => ts('Mailing Link Clickthrough'),
    'title_plural' => ts('Mailing Link Clickthroughs'),
    'description' => ts('Tracks when a TrackableURL is clicked by a recipient.'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Trackable URL Open ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'input_attrs' => [
        'label' => ts('ID'),
      ],
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'event_queue_id' => [
      'title' => ts('Event Queue ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to EventQueue'),
      'input_attrs' => [
        'label' => ts('Recipient'),
      ],
      'entity_reference' => [
        'entity' => 'MailingEventQueue',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'trackable_url_id' => [
      'title' => ts('Trackable Url ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to TrackableURL'),
      'input_attrs' => [
        'label' => ts('Mailing Link'),
      ],
      'entity_reference' => [
        'entity' => 'MailingTrackableURL',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'time_stamp' => [
      'title' => ts('Timestamp'),
      'sql_type' => 'timestamp',
      'input_type' => 'Date',
      'required' => TRUE,
      'description' => ts('When this trackable URL open occurred.'),
      'default' => 'CURRENT_TIMESTAMP',
      'input_attrs' => [
        'label' => ts('Opened Date'),
      ],
    ],
  ],
];
