<?php

use CRM_AfformAdmin_ExtensionUtil as E;

// This file declares a SavedSearch and SearchDisplay for viewing form submissions.
return [
  [
    'name' => 'AfAdmin_Submission_List',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'AfAdmin_Submission_List',
        'label' => E::ts('Form Submissions'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'AfformSubmission',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'contact_id.display_name',
            'submission_date',
            'status_id:label',
          ],
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
    'name' => 'AfAdmin_Submission_List_Display',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'AfAdmin_Submission_List_Display',
        'label' => E::ts('Form Submissions Table'),
        'saved_search_id.name' => 'AfAdmin_Submission_List',
        'type' => 'table',
        'settings' => [
          'actions' => TRUE,
          'limit' => 50,
          'classes' => [
            'table',
            'table-striped',
          ],
          'pager' => [
            'show_count' => TRUE,
            'expose_limit' => TRUE,
          ],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'dataType' => 'Integer',
              'label' => E::ts('Id'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'contact_id.display_name',
              'dataType' => 'String',
              'label' => E::ts('Submitted by'),
              'sortable' => TRUE,
              'link' => [
                'entity' => 'Contact',
                'action' => 'view',
                'join' => 'contact_id',
                'target' => '_blank',
              ],
              'empty_value' => E::ts('Anonymous'),
              'cssRules' => [
                [
                  'disabled',
                  'contact_id.display_name',
                  '=',
                ],
              ],
            ],
            [
              'type' => 'field',
              'key' => 'submission_date',
              'dataType' => 'Timestamp',
              'label' => E::ts('Submission Date/Time'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status_id:label',
              'dataType' => 'Integer',
              'label' => E::ts('Submission Status'),
              'sortable' => TRUE,
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => 'AfformSubmission',
                  'action' => 'view',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-external-link',
                  'text' => E::ts('View'),
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
                [
                  'path' => '',
                  'icon' => 'fa-check-square-o',
                  'text' => E::ts('Process'),
                  'style' => 'default',
                  'condition' => [
                    'status_id:name',
                    '=',
                    'Pending',
                  ],
                  'task' => 'process',
                  'entity' => 'AfformSubmission',
                  'action' => '',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
              ],
              'type' => 'buttons',
              'alignment' => '',
            ],
          ],
          'sort' => [
            [
              'submission_date',
              'ASC',
            ],
          ],
          'placeholder' => 5,
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
