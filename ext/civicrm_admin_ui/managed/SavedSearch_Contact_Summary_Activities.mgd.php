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
            'GROUP_CONCAT(UNIQUE Activity_ActivityContact_Contact_02.sort_name) AS GROUP_CONCAT_Activity_ActivityContact_Contact_02_sort_name',
            'GROUP_CONCAT(UNIQUE Activity_ActivityContact_Contact_03.sort_name) AS GROUP_CONCAT_Activity_ActivityContact_Contact_03_sort_name',
            'GROUP_CONCAT(UNIQUE Activity_ActivityContact_Contact_04.sort_name) AS GROUP_CONCAT_Activity_ActivityContact_Contact_04_sort_name',
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
            [
              'Contact AS Activity_ActivityContact_Contact_02',
              'LEFT',
              'ActivityContact',
              [
                'id',
                '=',
                'Activity_ActivityContact_Contact_02.activity_id',
              ],
              [
                'Activity_ActivityContact_Contact_02.record_type_id:name',
                '=',
                '"Activity Source"',
              ],
            ],
            [
              'Contact AS Activity_ActivityContact_Contact_03',
              'LEFT',
              'ActivityContact',
              [
                'id',
                '=',
                'Activity_ActivityContact_Contact_03.activity_id',
              ],
              [
                'Activity_ActivityContact_Contact_03.record_type_id:name',
                '=',
                '"Activity Targets"',
              ],
            ],
            [
              'Contact AS Activity_ActivityContact_Contact_04',
              'LEFT',
              'ActivityContact',
              [
                'id',
                '=',
                'Activity_ActivityContact_Contact_04.activity_id',
              ],
              [
                'Activity_ActivityContact_Contact_04.record_type_id:name',
                '=',
                '"Activity Assignees"',
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
              'label' => ts('Subject'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'activity_date_time',
              'label' => ts('Date'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status_id:label',
              'label' => ts('Status'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_CONCAT_Activity_ActivityContact_Contact_02_sort_name',
              'label' => ts('Added By'),
              'sortable' => TRUE,
              'link' => [
                'entity' => 'Contact',
                'action' => 'view',
                'join' => 'Activity_ActivityContact_Contact_02',
                'target' => '_blank',
              ],
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_CONCAT_Activity_ActivityContact_Contact_03_sort_name',
              'label' => ts('With'),
              'sortable' => TRUE,
              'link' => [
                'entity' => 'Contact',
                'action' => 'view',
                'join' => 'Activity_ActivityContact_Contact_03',
                'target' => '_blank',
              ],
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_CONCAT_Activity_ActivityContact_Contact_04_sort_name',
              'label' => ts('Assigned'),
              'sortable' => TRUE,
              'link' => [
                'entity' => 'Contact',
                'action' => 'view',
                'join' => 'Activity_ActivityContact_Contact_04',
                'target' => '_blank',
              ],
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
