<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Price_Set_Usage_Events',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Price_Set_Usage_Events',
        'label' => E::ts('Price Set Usage: Events'),
        'api_entity' => 'PriceSetEntity',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'PriceSetEntity_Event_entity_id_01.title',
            'PriceSetEntity_Event_entity_id_01.event_type_id:label',
            'PriceSetEntity_Event_entity_id_01.start_date',
            'PriceSetEntity_Event_entity_id_01.end_date',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [
            [
              'Event AS PriceSetEntity_Event_entity_id_01',
              'INNER',
              [
                'entity_id',
                '=',
                'PriceSetEntity_Event_entity_id_01.id',
              ],
              [
                'entity_table',
                '=',
                '\'civicrm_event\'',
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
    'name' => 'SavedSearch_Price_Set_Usage_Events_SearchDisplay_Price_Set_Usage_Events',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Price_Set_Usage_Events',
        'label' => E::ts('Price Set Usage: Events'),
        'saved_search_id.name' => 'Price_Set_Usage_Events',
        'type' => 'table',
        'settings' => [
          'description' => E::ts(NULL),
          'sort' => [
            [
              'PriceSetEntity_Event_entity_id_01.title',
              'ASC',
            ],
          ],
          'limit' => 50,
          'pager' => [
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'PriceSetEntity_Event_entity_id_01.title',
              'label' => E::ts('Event'),
              'sortable' => TRUE,
              'link' => [
                'path' => '',
                'entity' => 'Event',
                'action' => 'view',
                'join' => 'PriceSetEntity_Event_entity_id_01',
                'target' => 'crm-popup',
                'task' => '',
              ],
              'title' => E::ts('View Price Set Entity Event'),
            ],
            [
              'type' => 'field',
              'key' => 'PriceSetEntity_Event_entity_id_01.event_type_id:label',
              'label' => E::ts('Type'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'PriceSetEntity_Event_entity_id_01.start_date',
              'label' => E::ts('Dates'),
              'sortable' => TRUE,
              'rewrite' => '[PriceSetEntity_Event_entity_id_01.start_date] - [PriceSetEntity_Event_entity_id_01.end_date]',
            ],
          ],
          'actions' => FALSE,
          'classes' => ['table', 'table-striped'],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
