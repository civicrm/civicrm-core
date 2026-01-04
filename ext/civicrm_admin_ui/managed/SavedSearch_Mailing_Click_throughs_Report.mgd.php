<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Mailing_Click_throughs_Report',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Mailing_Click_throughs_Report',
        'label' => E::ts('Mailing Click-throughs'),
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'display_name',
            'GROUP_CONCAT(DISTINCT Contact_MailingEventQueue_contact_id_01_MailingEventQueue_MailingEventTrackableURLOpen_event_queue_id_01_MailingEventTrackableURLOpen_MailingTrackableURL_trackable_url_id_01.url) AS GROUP_CONCAT_Contact_MailingEventQueue_contact_id_01_MailingEventQueue_MailingEventTrackableURLOpen_event_queue_id_01_MailingEventTrackableURLOpen_MailingTrackableURL_trackable_url_id_01_url',
            'GROUP_CONCAT(DISTINCT Contact_MailingEventQueue_contact_id_01_MailingEventQueue_MailingEventTrackableURLOpen_event_queue_id_01.time_stamp) AS GROUP_CONCAT_Contact_MailingEventQueue_contact_id_01_MailingEventQueue_MailingEventTrackableURLOpen_event_queue_id_01_time_stamp',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => ['id'],
          'join' => [
            [
              'MailingEventQueue AS Contact_MailingEventQueue_contact_id_01',
              'INNER',
              [
                'id',
                '=',
                'Contact_MailingEventQueue_contact_id_01.contact_id',
              ],
            ],
            [
              'MailingEventTrackableURLOpen AS Contact_MailingEventQueue_contact_id_01_MailingEventQueue_MailingEventTrackableURLOpen_event_queue_id_01',
              'INNER',
              [
                'Contact_MailingEventQueue_contact_id_01.id',
                '=',
                'Contact_MailingEventQueue_contact_id_01_MailingEventQueue_MailingEventTrackableURLOpen_event_queue_id_01.event_queue_id',
              ],
            ],
            [
              'MailingTrackableURL AS Contact_MailingEventQueue_contact_id_01_MailingEventQueue_MailingEventTrackableURLOpen_event_queue_id_01_MailingEventTrackableURLOpen_MailingTrackableURL_trackable_url_id_01',
              'INNER',
              [
                'Contact_MailingEventQueue_contact_id_01_MailingEventQueue_MailingEventTrackableURLOpen_event_queue_id_01.trackable_url_id',
                '=',
                'Contact_MailingEventQueue_contact_id_01_MailingEventQueue_MailingEventTrackableURLOpen_event_queue_id_01_MailingEventTrackableURLOpen_MailingTrackableURL_trackable_url_id_01.id',
              ],
            ],
          ],
          'having' => [],
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_Mailing_Click_throughs_Report_SearchDisplay_Mailing_Click_throughs_Results',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Mailing_Click_throughs_Results',
        'label' => E::ts('Mailing Click-throughs'),
        'saved_search_id.name' => 'Mailing_Click_throughs_Report',
        'type' => 'table',
        'settings' => [
          'description' => '',
          'sort' => [
            [
              'Contact_MailingEventQueue_contact_id_01_MailingEventQueue_MailingEventTrackableURLOpen_event_queue_id_01.time_stamp',
              'DESC',
            ],
          ],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'display_name',
              'label' => E::ts('Display Name'),
              'sortable' => TRUE,
              'link' => [
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => '',
                'target' => '',
                'task' => '',
              ],
              'title' => E::ts('View Contact'),
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_CONCAT_Contact_MailingEventQueue_contact_id_01_MailingEventQueue_MailingEventTrackableURLOpen_event_queue_id_01_MailingEventTrackableURLOpen_MailingTrackableURL_trackable_url_id_01_url',
              'label' => E::ts('URL'),
              'sortable' => TRUE,
              'link' => [
                'path' => '[GROUP_CONCAT_Contact_MailingEventQueue_contact_id_01_MailingEventQueue_MailingEventTrackableURLOpen_event_queue_id_01_MailingEventTrackableURLOpen_MailingTrackableURL_trackable_url_id_01_url]',
                'entity' => '',
                'action' => '',
                'join' => '',
                'target' => '',
                'task' => '',
              ],
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_CONCAT_Contact_MailingEventQueue_contact_id_01_MailingEventQueue_MailingEventTrackableURLOpen_event_queue_id_01_time_stamp',
              'label' => E::ts('Date'),
              'sortable' => TRUE,
            ],
          ],
          'actions' => TRUE,
          'classes' => ['table', 'table-striped'],
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
