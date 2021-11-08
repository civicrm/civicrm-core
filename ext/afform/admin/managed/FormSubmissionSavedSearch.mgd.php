<?php

use CRM_AfformAdmin_ExtensionUtil as E;

// This file declares a SavedSearch and SearchDisplay for viewing form submissions.
return [
  [
    'name' => 'AfAdmin_Submission_List',
    'entity' => 'SavedSearch',
    'update' => 'unmodified',
    'cleanup' => 'unused',
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
          ],
        ],
      ],
    ],
  ],
  [
    'name' => 'AfAdmin_Submission_List_Display',
    'entity' => 'SearchDisplay',
    'update' => 'unmodified',
    'cleanup' => 'unused',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'AfAdmin_Submission_List_Display',
        'label' => E::ts('Form Submissions Table'),
        'saved_search_id.name' => 'AfAdmin_Submission_List',
        'type' => 'table',
        'actions' => TRUE,
        'acl_bypass' => FALSE,
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
          ],
          'sort' => [
            [
              'submission_date',
              'ASC',
            ],
          ],
        ],
      ],
    ],
  ],
];
