<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

// Temporary check can be removed when moving this file to the civi_case extension.
if (!CRM_Core_Component::isEnabled('CiviCase')) {
  return [];
}

return [
  [
    'name' => 'SavedSearch_Contact_Summary_Cases',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contact_Summary_Cases',
        'label' => E::ts('Contact Summary Cases'),
        'form_values' => [
          'join' => [
            'Case_CaseContact_Contact_01_Contact_RelationshipCache_Contact_01' => 'Case Manager',
            'Case_CaseActivity_Activity_01' => 'Recent Completed Activity',
            'Case_CaseActivity_Activity_02' => 'Next Scheduled Activity',
          ],
        ],
        'api_entity' => 'Case',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'case_type_id:label',
            'subject',
            'status_id:label',
            'GROUP_CONCAT(DISTINCT Case_CaseContact_Contact_01_Contact_RelationshipCache_Contact_01.sort_name ORDER BY Case_CaseContact_Contact_01.sort_name ASC) AS GROUP_CONCAT_Case_CaseContact_Contact_01_Contact_RelationshipCache_Contact_01_sort_name_Case_CaseContact_Contact_01_sort_name',
            'GROUP_FIRST(Case_CaseActivity_Activity_01.activity_type_id:label ORDER BY Case_CaseActivity_Activity_01.activity_date_time DESC) AS GROUP_FIRST_Case_CaseActivity_Activity_01_activity_type_id_label_Case_CaseActivity_Activity_01_activity_date_time',
            'GROUP_FIRST(Case_CaseActivity_Activity_01.activity_date_time ORDER BY Case_CaseActivity_Activity_01.activity_date_time DESC) AS GROUP_FIRST_Case_CaseActivity_Activity_01_activity_date_time_Case_CaseActivity_Activity_01_activity_date_time',
            'GROUP_FIRST(Case_CaseActivity_Activity_02.activity_type_id:label ORDER BY Case_CaseActivity_Activity_02.activity_date_time ASC) AS GROUP_FIRST_Case_CaseActivity_Activity_02_activity_type_id_label_Case_CaseActivity_Activity_02_activity_date_time',
            'GROUP_FIRST(Case_CaseActivity_Activity_02.activity_date_time ORDER BY Case_CaseActivity_Activity_02.activity_date_time ASC) AS GROUP_FIRST_Case_CaseActivity_Activity_02_activity_date_time_Case_CaseActivity_Activity_02_activity_date_time',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [
            'id',
            'Case_CaseContact_Contact_01.id',
          ],
          'join' => [
            [
              'Contact AS Case_CaseContact_Contact_01',
              'LEFT',
              'CaseContact',
              [
                'id',
                '=',
                'Case_CaseContact_Contact_01.case_id',
              ],
            ],
            [
              'Contact AS Case_CaseContact_Contact_01_Contact_RelationshipCache_Contact_01',
              'LEFT',
              'RelationshipCache',
              [
                'Case_CaseContact_Contact_01.id',
                '=',
                'Case_CaseContact_Contact_01_Contact_RelationshipCache_Contact_01.far_contact_id',
              ],
              [
                'Case_CaseContact_Contact_01_Contact_RelationshipCache_Contact_01.near_relation:name',
                '=',
                '"Homeless Services Coordinator"',
              ],
              [
                'Case_CaseContact_Contact_01_Contact_RelationshipCache_Contact_01.is_current',
                '=',
                TRUE,
              ],
              [
                'Case_CaseContact_Contact_01_Contact_RelationshipCache_Contact_01.case_id',
                '=',
                'id',
              ],
            ],
            [
              'Activity AS Case_CaseActivity_Activity_01',
              'LEFT',
              'CaseActivity',
              [
                'id',
                '=',
                'Case_CaseActivity_Activity_01.case_id',
              ],
              [
                'Case_CaseActivity_Activity_01.status_id:name',
                '=',
                '"Completed"',
              ],
            ],
            [
              'Activity AS Case_CaseActivity_Activity_02',
              'LEFT',
              'CaseActivity',
              [
                'id',
                '=',
                'Case_CaseActivity_Activity_02.case_id',
              ],
              [
                'Case_CaseActivity_Activity_02.status_id:name',
                '=',
                '"Scheduled"',
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
    'name' => 'SavedSearch_Contact_Summary_Cases_SearchDisplay_Contact_Summary_Cases_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contact_Summary_Cases_Table',
        'label' => E::ts('Contact Summary Cases Table'),
        'saved_search_id.name' => 'Contact_Summary_Cases',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [],
          'limit' => 50,
          'pager' => [
            'hide_single' => TRUE,
          ],
          'placeholder' => 1,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'case_type_id:label',
              'label' => E::ts('Case Type'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'subject',
              'label' => E::ts('Subject'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status_id:label',
              'label' => E::ts('Status'),
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_CONCAT_Case_CaseContact_Contact_01_Contact_RelationshipCache_Contact_01_sort_name_Case_CaseContact_Contact_01_sort_name',
              'label' => E::ts('Case Manager'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_FIRST_Case_CaseActivity_Activity_01_activity_date_time_Case_CaseActivity_Activity_01_activity_date_time',
              'format' => '',
              'rewrite' => '[GROUP_FIRST_Case_CaseActivity_Activity_01_activity_type_id_label_Case_CaseActivity_Activity_01_activity_date_time]: [GROUP_FIRST_Case_CaseActivity_Activity_01_activity_date_time_Case_CaseActivity_Activity_01_activity_date_time]',
              'label' => E::ts('Last Completed'),
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_FIRST_Case_CaseActivity_Activity_02_activity_date_time_Case_CaseActivity_Activity_02_activity_date_time',
              'label' => E::ts('Next Scheduled'),
              'rewrite' => '[GROUP_FIRST_Case_CaseActivity_Activity_02_activity_type_id_label_Case_CaseActivity_Activity_02_activity_date_time]: [GROUP_FIRST_Case_CaseActivity_Activity_02_activity_date_time_Case_CaseActivity_Activity_02_activity_date_time]',
              'cssRules' => [],
            ],
            [
              'label' => E::ts('Activities'),
              'rewrite' => '',
              'alignment' => '',
              'type' => 'subsearch',
              'icons' => [
                [
                  'icon' => 'fa-list-check',
                  'side' => 'left',
                  'if' => [],
                ],
              ],
              'subsearch' => [
                'search' => 'Contact_Summary_Case_Activities',
                'filters' => [
                  [
                    'subsearch_field' => 'Activity_CaseActivity_Case_01.id',
                    'parent_field' => 'id',
                  ],
                ],
                'display' => 'Contact_Summary_Case_Activities_Table',
              ],
            ],
            [
              'links' => [
                [
                  'entity' => 'Case',
                  'action' => 'view',
                  'join' => '',
                  'target' => '',
                  'icon' => 'fa-external-link',
                  'text' => E::ts('Manage'),
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'conditions' => [],
                ],
              ],
              'type' => 'links',
              'alignment' => 'text-right',
            ],
          ],
          'actions' => FALSE,
          'classes' => ['table', 'table-striped'],
          'columnMode' => 'custom',
          'nested' => [
            'search' => 'Contact_Summary_Case_Activities',
            'filters' => [
              [
                'field' => 'Activity_CaseActivity_Case_01.id',
                'data' => 'id',
              ],
            ],
            'display' => 'Contact_Summary_Case_Activities_Table',
          ],
          'toolbar' => [
            [
              'path' => 'civicrm/case/add?reset=1&action=add&cid=[Case_CaseContact_Contact_01.id]&context=case',
              'icon' => 'fa-circle-plus',
              'text' => E::ts('Add Case'),
              'style' => 'default',
              'conditions' => [
                [
                  'check user permission',
                  '=',
                  ['add cases'],
                ],
              ],
              'task' => '',
              'entity' => '',
              'action' => '',
              'join' => '',
              'target' => '',
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
