<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_My_account',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'My_account',
        'label' => E::ts('My account'),
        'api_entity' => 'UFMatch',
        'api_params' => [
          'version' => 4,
          'select' => [
            'uf_id',
            'contact_id',
            'contact_id.display_name',
            'uf_name',
          ],
          'orderBy' => [],
          'where' => [
            [
              'contact_id',
              '=',
              'user_contact_id',
            ],
          ],
          'groupBy' => [],
          'join' => [
            [
              'Contact AS UFMatch_Contact_contact_id_01',
              'LEFT',
              [
                'contact_id',
                '=',
                'UFMatch_Contact_contact_id_01.id',
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
    'name' => 'SavedSearch_My_account_SearchDisplay_My_account',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'My_account',
        'label' => E::ts('My account'),
        'saved_search_id.name' => 'My_account',
        'type' => 'grid',
        'settings' => [
          'colno' => '2',
          'limit' => 0,
          'sort' => [],
          'pager' => FALSE,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'contact_id.display_name',
              'dataType' => 'String',
              'label' => E::ts('Contact'),
              'link' => [
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => 'contact_id',
                'target' => '',
              ],
              'title' => E::ts('View Contact'),
              'break' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'uf_name',
              'dataType' => 'String',
              'label' => E::ts('Username'),
              'break' => TRUE,
            ],
            [
              'size' => '',
              'links' => [
                [
                  'path' => '/civicrm/user/edit#?User1=[uf_id]',
                  'icon' => 'fa-key',
                  'text' => E::ts('Update account'),
                  'style' => 'default',
                  'condition' => [],
                  'task' => '',
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                ],
                [
                  'path' => '/civicrm/admin/user/password',
                  'icon' => 'fa-keyboard',
                  'text' => E::ts('Update password'),
                  'style' => 'default',
                  'condition' => [],
                  'task' => '',
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                ],
              ],
              'type' => 'buttons',
              'label' => E::ts('Actions'),
              'break' => TRUE,
            ],
          ],
          'placeholder' => 5,
        ],
        'acl_bypass' => TRUE,
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
