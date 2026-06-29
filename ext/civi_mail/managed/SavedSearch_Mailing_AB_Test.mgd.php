<?php
use CRM_Mailing_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Mailing_AB_Test',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Mailing_AB_Test',
        'label' => E::ts('Mailing A/B Tests'),
        'api_entity' => 'MailingAB',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'name',
            'status',
            'testing_criteria',
            'created_date',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_Mailing_AB_Test_SearchDisplay_Mailing_AB_Test_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Mailing_AB_Test_Table',
        'label' => E::ts('Mailing A/B Tests Table'),
        'saved_search_id.name' => 'Mailing_AB_Test',
        'type' => 'table',
        'settings' => [
          'description' => '',
          'sort' => [
            ['created_date', 'DESC'],
          ],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'name',
              'label' => 'Name',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status:label',
              'label' => 'Status',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'testing_criteria:label',
              'label' => 'Test Type',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'created_date',
              'label' => 'Created',
              'sortable' => TRUE,
            ],
            [
              'links' => [
                [
                  'icon' => 'fa-pencil',
                  'text' => 'Continue',
                  'style' => 'default',
                  'path' => 'civicrm/a/#/abtest/[id]',
                  'condition' => ['status', '=', 'Draft'],
                ],
                [
                  'icon' => 'fa-bar-chart',
                  'text' => E::ts('Results'),
                  'style' => 'default',
                  'path' => 'civicrm/a/#/abtest/[id]',
                  'condition' => ['status', '!=', 'Draft'],
                ],
              ],
              'type' => 'links',
              'alignment' => 'text-right',
            ],
          ],
          'actions' => FALSE,
          'classes' => ['table', 'table-striped'],
          'toolbar' => [
            [
              'path' => 'civicrm/a/#/abtest/new',
              'icon' => 'fa-flask',
              'text' => 'New A/B Test',
              'style' => 'primary',
            ],
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
