<?php

return [
  'name' => 'MailingTrackableURL',
  'table' => 'civicrm_mailing_trackable_url',
  'class' => 'CRM_Mailing_DAO_MailingTrackableURL',
  'getInfo' => fn() => [
    'title' => ts('Mailing Link'),
    'title_plural' => ts('Mailing Links'),
    'description' => ts('Stores URLs for which we should track click-throughs from mailings'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Trackable URL ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'url' => [
      'title' => ts('Url'),
      'sql_type' => 'text',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('The URL to be tracked.'),
    ],
    'mailing_id' => [
      'title' => ts('Mailing ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to the mailing'),
      'input_attrs' => [
        'label' => ts('Mailing'),
      ],
      'entity_reference' => [
        'entity' => 'Mailing',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
