<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_My_Account',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'My_Account',
        'label' => E::ts('My Account'),
        'api_entity' => 'User',
        'api_params' => [
          'version' => 4,
          'select' => [
            'uf_id',
            'contact_id',
            'contact_id.display_name',
            'uf_name',
            'username',
            'timezone',
            'language:label',
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
              'Contact AS User_Contact_contact_id_01',
              'LEFT',
              [
                'contact_id',
                '=',
                'User_Contact_contact_id_01.id',
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
    'name' => 'SavedSearch_My_Account_SearchDisplay_My_Account',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'My_Account',
        'label' => E::ts('My Account'),
        'saved_search_id.name' => 'My_Account',
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
              'key' => 'username',
              'label' => E::ts('Username'),
              'break' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'uf_name',
              'label' => E::ts('Password Reset Email'),
              'break' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'timezone',
              'label' => E::ts('Timezone'),
              'break' => TRUE,
              'empty' => E::ts('System default'),
            ],
            [
              'type' => 'field',
              'key' => 'language:label',
              'label' => E::ts('Language'),
              'break' => TRUE,
              'empty' => E::ts('System default'),
            ],
            [
              'size' => '',
              'links' => [
                [
                  'path' => '/civicrm/my-account/edit#?User1=[uf_id]',
                  'icon' => 'fa-user',
                  'text' => E::ts('Update Account'),
                  'style' => 'default',
                  'condition' => [],
                  'task' => '',
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                ],
                [
                  'path' => '/civicrm/my-account/password',
                  'icon' => 'fa-keyboard',
                  'text' => E::ts('Change Password'),
                  'style' => 'default',
                  'condition' => [],
                  'task' => '',
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => 'crm-popup',
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
