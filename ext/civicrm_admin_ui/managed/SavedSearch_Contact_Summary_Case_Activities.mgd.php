<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

// Temporary check can be removed when moving this file to the civi_case extension.
if (!CRM_Core_Component::isEnabled('CiviCase')) {
  return [];
}

return [
  [
    'name' => 'SavedSearch_Contact_Summary_Case_Activities',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contact_Summary_Case_Activities',
        'label' => E::ts('Contact Summary Case Activities'),
        'form_values' => [
          'join' => [
            'Activity_ActivityContact_Contact_01' => 'Target Contacts',
            'Activity_ActivityContact_Contact_02' => 'Source Contact',
            'Activity_ActivityContact_Contact_03' => 'Assignee Contacts',
          ],
        ],
        'api_entity' => 'Activity',
        'api_params' => [
          'version' => 4,
          'select' => [
            'activity_date_time',
            'subject',
            'activity_type_id:label',
            'status_id:label',
            'GROUP_CONCAT(DISTINCT Activity_ActivityContact_Contact_02.sort_name) AS GROUP_CONCAT_Activity_ActivityContact_Contact_02_sort_name',
            'GROUP_CONCAT(DISTINCT Activity_ActivityContact_Contact_01.sort_name ORDER BY Activity_ActivityContact_Contact_01.sort_name ASC) AS GROUP_CONCAT_Activity_ActivityContact_Contact_01_sort_name_Activity_ActivityContact_Contact_01_sort_name',
            'Activity_CaseActivity_Case_01.id',
            'GROUP_CONCAT(DISTINCT Activity_ActivityContact_Contact_03.sort_name) AS GROUP_CONCAT_Activity_ActivityContact_Contact_03_sort_name',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [
            'id',
            'Activity_CaseActivity_Case_01.id',
          ],
          'join' => [
            [
              'Contact AS Activity_ActivityContact_Contact_01',
              'LEFT',
              'ActivityContact',
              [
                'id',
                '=',
                'Activity_ActivityContact_Contact_01.activity_id',
              ],
              [
                'Activity_ActivityContact_Contact_01.record_type_id:name',
                '=',
                '"Activity Targets"',
              ],
            ],
            [
              'Case AS Activity_CaseActivity_Case_01',
              'LEFT',
              'CaseActivity',
              [
                'id',
                '=',
                'Activity_CaseActivity_Case_01.activity_id',
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
                '"Activity Assignees"',
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
    'name' => 'SavedSearch_Contact_Summary_Case_Activities_SearchDisplay_Contact_Summary_Case_Activities_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contact_Summary_Case_Activities_Table',
        'label' => E::ts('Contact Summary Case Activities Table'),
        'saved_search_id.name' => 'Contact_Summary_Case_Activities',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'activity_date_time',
              'DESC',
            ],
          ],
          'limit' => 10,
          'pager' => [
            'show_count' => TRUE,
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'activity_date_time',
              'label' => E::ts('Date'),
              'sortable' => TRUE,
              'format' => 'dateformatshortdate',
            ],
            [
              'type' => 'field',
              'key' => 'subject',
              'label' => E::ts('Subject'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'activity_type_id:label',
              'label' => E::ts('Activity Type'),
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
              'key' => 'GROUP_CONCAT_Activity_ActivityContact_Contact_01_sort_name_Activity_ActivityContact_Contact_01_sort_name',
              'label' => E::ts('With'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_CONCAT_Activity_ActivityContact_Contact_02_sort_name',
              'label' => E::ts('Reporter'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_CONCAT_Activity_ActivityContact_Contact_03_sort_name',
              'label' => E::ts('Assignee'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status_id:label',
              'label' => E::ts('Status'),
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
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-external-link',
                  'text' => E::ts('View Activity'),
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'conditions' => [],
                ],
                [
                  'entity' => 'Activity',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Update Activity'),
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'conditions' => [],
                ],
                [
                  'entity' => 'Activity',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete Activity'),
                  'style' => 'danger',
                  'path' => '',
                  'task' => '',
                  'conditions' => [],
                ],
              ],
              'type' => 'menu',
              'alignment' => 'text-right',
            ],
          ],
          'actions' => FALSE,
          'classes' => ['table', 'table-striped'],
          'columnMode' => 'custom',
          'cssRules' => [],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
