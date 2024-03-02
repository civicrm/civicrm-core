<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Find_Participants',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Find_Participants',
        'label' => E::ts('Find Participants'),
        'api_entity' => 'Participant',
        'api_params' => [
          'version' => 4,
          'select' => [
            'contact_id.sort_name',
            'event_id.title',
            'fee_level',
            'fee_amount',
            'register_date',
            'Participant_Event_event_id_01.start_date',
            'Participant_Event_event_id_01.end_date',
            'status_id:label',
            'role_id:label',
            'Participant_Contact_contact_id_01_Contact_Email_contact_id_01.email',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [
            [
              'Event AS Participant_Event_event_id_01',
              'LEFT',
              [
                'event_id',
                '=',
                'Participant_Event_event_id_01.id',
              ],
            ],
            [
              'Contact AS Participant_Contact_contact_id_01',
              'LEFT',
              [
                'contact_id',
                '=',
                'Participant_Contact_contact_id_01.id',
              ],
            ],
            [
              'Email AS Participant_Contact_contact_id_01_Contact_Email_contact_id_01',
              'LEFT',
              [
                'Participant_Contact_contact_id_01.id',
                '=',
                'Participant_Contact_contact_id_01_Contact_Email_contact_id_01.contact_id',
              ],
            ],
          ],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Find_Participants_SearchDisplay_Find_Participants_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Find_Participants_Table_1',
        'label' => E::ts('Find Participants Table 1'),
        'saved_search_id.name' => 'Find_Participants',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [],
          'limit' => 50,
          'pager' => [
            'show_count' => TRUE,
            'expose_limit' => TRUE,
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'contact_id.sort_name',
              'dataType' => 'String',
              'label' => E::ts('Participant'),
              'sortable' => TRUE,
              'link' => [
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => 'contact_id',
                'target' => '',
              ],
              'title' => E::ts('View Contact'),
              'tally' => [
                'fn' => NULL,
              ],
            ],
            [
              'type' => 'field',
              'key' => 'event_id.title',
              'dataType' => 'String',
              'label' => E::ts('Event'),
              'sortable' => TRUE,
              'link' => [
                'path' => '',
                'entity' => 'Event',
                'action' => 'view',
                'join' => 'event_id',
                'target' => '',
              ],
              'title' => E::ts('View Event'),
              'tally' => [
                'fn' => NULL,
              ],
            ],
            [
              'type' => 'field',
              'key' => 'fee_level',
              'dataType' => 'Text',
              'label' => E::ts('Fee Level'),
              'sortable' => TRUE,
              'tally' => [
                'fn' => NULL,
              ],
            ],
            [
              'type' => 'field',
              'key' => 'fee_amount',
              'dataType' => 'Money',
              'label' => E::ts('Amount'),
              'sortable' => TRUE,
              'tally' => [
                'fn' => 'SUM',
              ],
            ],
            [
              'type' => 'field',
              'key' => 'register_date',
              'dataType' => 'Timestamp',
              'label' => E::ts('Registered'),
              'sortable' => TRUE,
              'tally' => [
                'fn' => NULL,
              ],
            ],
            [
              'type' => 'html',
              'key' => 'Participant_Event_event_id_01.start_date',
              'dataType' => 'Timestamp',
              'label' => E::ts('Event Date(s)'),
              'sortable' => TRUE,
              'rewrite' => '[Participant_Event_event_id_01.start_date] - <br />'."\n"
                .'[Participant_Event_event_id_01.end_date]',
            ],
            [
              'type' => 'field',
              'key' => 'status_id:label',
              'dataType' => 'Integer',
              'label' => E::ts('Status'),
              'sortable' => TRUE,
              'tally' => [
                'fn' => NULL,
              ],
            ],
            [
              'type' => 'field',
              'key' => 'role_id:label',
              'dataType' => 'String',
              'label' => E::ts('Role'),
              'sortable' => TRUE,
              'tally' => [
                'fn' => NULL,
              ],
            ],
            [
              'text' => '',
              'style' => 'default',
              'size' => 'btn-xs',
              'icon' => 'fa-bars',
              'links' => [
                [
                  'entity' => 'Participant',
                  'action' => 'view',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-external-link',
                  'text' => E::ts('View Participant'),
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
                [
                  'entity' => 'Participant',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Update Participant'),
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
                [
                  'entity' => 'Participant',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete Participant'),
                  'style' => 'danger',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
              ],
              'type' => 'menu',
              'alignment' => 'text-right',
            ],
          ],
          'actions' => FALSE,
          'classes' => [
            'table',
            'table-striped',
            'crm-sticky-header',
          ],
          'headerCount' => TRUE,
          'tally' => [
            'label' => E::ts('Total'),
          ],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
