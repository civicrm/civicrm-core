<?php

// Conditionally exclude CiviCase activities
$excludeCaseActivities = (CRM_Core_Component::isEnabled('CiviCase') && !\Civi::settings()->get('civicaseShowCaseActivities'));
return [
  [
    'name' => 'SavedSearch_Contact_Summary_Activities',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contact_Summary_Activities',
        'label' => ts('Contact Summary Activities'),
        'api_entity' => 'Activity',
        'api_params' => [
          'version' => 4,
          'select' => [
            'activity_type_id:label',
            'subject',
            'activity_date_time',
            'status_id:label',
            'source_contact_id',
            'target_contact_id',
            'assignee_contact_id',
          ],
          'orderBy' => [],
          'where' => $excludeCaseActivities ? [['case_id', 'IS EMPTY']] : [],
          'groupBy' => [
            'id',
          ],
          'join' => [
            [
              'Contact AS Activity_ActivityContact_Contact_01',
              'INNER',
              'ActivityContact',
              [
                'id',
                '=',
                'Activity_ActivityContact_Contact_01.activity_id',
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
    'name' => 'SavedSearch_Contact_Summary_Activities_SearchDisplay_Contact_Summary_Activities_Tab',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contact_Summary_Activities_Tab',
        'label' => ts('Contact Summary Activities Tab'),
        'saved_search_id.name' => 'Contact_Summary_Activities',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'activity_date_time',
              'DESC',
            ],
          ],
          'limit' => 50,
          'pager' => [
            'hide_single' => TRUE,
            'show_count' => TRUE,
            'expose_limit' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'activity_type_id:label',
              'dataType' => 'Integer',
              'label' => ts('Type'),
              'sortable' => TRUE,
              'icons' => [
                [
                  'field' => 'activity_type_id:icon',
                  'side' => 'left',
                ],
              ],
            ],
            [
              'type' => 'field',
              'key' => 'subject',
              'dataType' => 'String',
              'label' => ts('Subject'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'activity_date_time',
              'dataType' => 'Timestamp',
              'label' => ts('Date'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status_id:label',
              'dataType' => 'Integer',
              'label' => ts('Status'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'source_contact_id',
              'dataType' => 'String',
              'label' => ts('Added By'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'target_contact_id',
              'dataType' => 'String',
              'label' => ts('With'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'target_contact_id',
              'dataType' => 'String',
              'label' => ts('Assigned'),
              'sortable' => TRUE,
            ],
            [
              'text' => '',
              'style' => 'default',
              'size' => 'btn-xs',
              'icon' => 'fa-bars',
              'links' => [
                [
                  'entity' => 'Activity',
                  'action' => 'view',
                  'target' => 'crm-popup',
                  'icon' => 'fa-external-link',
                  'text' => ts('View Activity'),
                  'style' => 'default',
                ],
                [
                  'entity' => 'Activity',
                  'action' => 'update',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => ts('Update Activity'),
                  'style' => 'default',
                ],
                [
                  'entity' => 'Activity',
                  'action' => 'delete',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => ts('Delete Activity'),
                  'style' => 'danger',
                ],
              ],
              'type' => 'menu',
              'alignment' => 'text-right',
            ],
          ],
          'classes' => [
            'table',
            'table-striped',
          ],
          'toolbar' => [
            [
              'action' => 'add',
              'entity' => 'Activity',
              'text' => ts('Add Activity'),
              'icon' => 'fa-plus',
              'style' => 'primary',
              'target' => 'crm-popup',
            ],
          ],
        ],
      ],
      'match' => [
        'name',
        'saved_search_id',
      ],
    ],
  ],
];
