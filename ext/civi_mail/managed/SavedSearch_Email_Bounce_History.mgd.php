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
        'label' => E::ts('Email_Bounce_History'),
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
              'label' => E::ts('Date'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'bounce_type_id:label',
              'label' => E::ts('Type'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'bounce_reason',
              'label' => E::ts('Reason'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'MailingEventBounce_MailingEventQueue_event_queue_id_01_MailingEventQueue_MailingJob_job_id_01_MailingJob_Mailing_mailing_id_01.name',
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
        'acl_bypass' => FALSE,
      ],
      'match' => [
        'name',
        'saved_search_id',
      ],
    ],
  ],
];
