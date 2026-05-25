<?php
use CRM_Mailing_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Email_Bounce_History',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Email_Bounce_History',
        'label' => E::ts('Email Bounce History'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'MailingEventBounce',
        'api_params' => [
          'version' => 4,
          'select' => [
            'time_stamp',
            'bounce_type_id:label',
            'bounce_reason',
            'MailingEventBounce_MailingEventQueue_event_queue_id_01_MailingEventQueue_MailingJob_job_id_01_MailingJob_Mailing_mailing_id_01.name',
            'MailingEventBounce_MailingEventQueue_event_queue_id_01.contact_id.display_name',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [
            [
              'MailingEventQueue AS MailingEventBounce_MailingEventQueue_event_queue_id_01',
              'INNER',
              [
                'event_queue_id',
                '=',
                'MailingEventBounce_MailingEventQueue_event_queue_id_01.id',
              ],
            ],
            [
              'MailingJob AS MailingEventBounce_MailingEventQueue_event_queue_id_01_MailingEventQueue_MailingJob_job_id_01',
              'INNER',
              [
                'MailingEventBounce_MailingEventQueue_event_queue_id_01.job_id',
                '=',
                'MailingEventBounce_MailingEventQueue_event_queue_id_01_MailingEventQueue_MailingJob_job_id_01.id',
              ],
            ],
            [
              'Mailing AS MailingEventBounce_MailingEventQueue_event_queue_id_01_MailingEventQueue_MailingJob_job_id_01_MailingJob_Mailing_mailing_id_01',
              'INNER',
              [
                'MailingEventBounce_MailingEventQueue_event_queue_id_01_MailingEventQueue_MailingJob_job_id_01.mailing_id',
                '=',
                'MailingEventBounce_MailingEventQueue_event_queue_id_01_MailingEventQueue_MailingJob_job_id_01_MailingJob_Mailing_mailing_id_01.id',
              ],
            ],
            [
              'Contact AS MailingEventBounce_MailingEventQueue_event_queue_id_01_MailingEventQueue_Contact_contact_id_01',
              'LEFT',
              [
                'MailingEventBounce_MailingEventQueue_event_queue_id_01.contact_id',
                '=',
                'MailingEventBounce_MailingEventQueue_event_queue_id_01_MailingEventQueue_Contact_contact_id_01.id',
              ],
            ],
          ],
          'having' => [],
        ],
        'expires_date' => NULL,
        'description' => NULL,
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Email_Bounce_History_SearchDisplay_Email_Bounce_History_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Email_Bounce_History_Table',
        'label' => E::ts('Email Bounce History'),
        'saved_search_id.name' => 'Email_Bounce_History',
        'type' => 'table',
        'settings' => [
          'description' => '',
          'sort' => [
            [
              'time_stamp',
              'DESC',
            ],
          ],
          'limit' => 10,
          'pager' => [
            'hide_single' => TRUE,
          ],
          'placeholder' => 3,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'time_stamp',
              'dataType' => 'Timestamp',
              'label' => E::ts('Date'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'bounce_type_id:label',
              'dataType' => 'Integer',
              'label' => E::ts('Type'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'bounce_reason',
              'dataType' => 'String',
              'label' => E::ts('Reason'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'MailingEventBounce_MailingEventQueue_event_queue_id_01_MailingEventQueue_MailingJob_job_id_01_MailingJob_Mailing_mailing_id_01.name',
              'dataType' => 'String',
              'label' => E::ts('Mailing'),
              'sortable' => FALSE,
              'link' => [
                'path' => '',
                'entity' => 'Mailing',
                'action' => 'view',
                'join' => 'MailingEventBounce_MailingEventQueue_event_queue_id_01_MailingEventQueue_MailingJob_job_id_01_MailingJob_Mailing_mailing_id_01',
                'target' => 'crm-popup',
              ],
              'title' => NULL,
            ],
          ],
          'actions' => FALSE,
          'classes' => [
            'table',
            'table-striped',
          ],
          'noResultsText' => '',
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Email_Bounce_History_SearchDisplay_Mailing_Bounces',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Mailing_Bounces',
        'label' => E::ts('Mailing Bounces'),
        'saved_search_id.name' => 'Email_Bounce_History',
        'type' => 'table',
        'settings' => [
          'description' => '',
          'sort' => [
            [
              'time_stamp',
              'DESC',
            ],
          ],
          'limit' => 25,
          'pager' => [
            'hide_single' => TRUE,
            'expose_limit' => TRUE,
            'show_count' => TRUE,
          ],
          'placeholder' => 3,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'time_stamp',
              'dataType' => 'Timestamp',
              'label' => E::ts('Date'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'bounce_type_id:label',
              'dataType' => 'Integer',
              'label' => E::ts('Type'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'MailingEventBounce_MailingEventQueue_event_queue_id_01.contact_id.display_name',
              'dataType' => 'String',
              'label' => E::ts('Name'),
              'sortable' => TRUE,
              'link' => [
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => 'MailingEventBounce_MailingEventQueue_event_queue_id_01.contact_id',
                'target' => '',
                'task' => '',
              ],
              'title' => E::ts('View Contact'),
            ],
            [
              'type' => 'field',
              'key' => 'bounce_reason',
              'dataType' => 'String',
              'label' => E::ts('Reason'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'MailingEventBounce_MailingEventQueue_event_queue_id_01_MailingEventQueue_MailingJob_job_id_01_MailingJob_Mailing_mailing_id_01.name',
              'dataType' => 'String',
              'label' => E::ts('Mailing'),
              'sortable' => FALSE,
              'link' => [
                'path' => '',
                'entity' => 'Mailing',
                'action' => 'view',
                'join' => 'MailingEventBounce_MailingEventQueue_event_queue_id_01_MailingEventQueue_MailingJob_job_id_01_MailingJob_Mailing_mailing_id_01',
                'target' => 'crm-popup',
              ],
              'title' => NULL,
            ],
          ],
          'actions' => TRUE,
          'classes' => [
            'table',
            'table-striped',
          ],
          'noResultsText' => '',
          'actions_display_mode' => 'menu',
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
