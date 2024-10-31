<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

// Temporary check can be removed when moving this file to the civi_contribute extension.
if (!CRM_Core_Component::isEnabled('CiviContribute')) {
  return [];
}

return [
  [
    'name' => 'SavedSearch_Manage_Contribution_Pages',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Manage_Contribution_Pages',
        'label' => E::ts('Manage Contribution Pages'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'ContributionPage',
        'api_params' => [
          'version' => 4,
          'select' => [
            'title',
            'id',
            'is_active',
            'financial_type_id:label',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
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
    'name' => 'SavedSearch_Manage_Contribution_Pages_SearchDisplay_Manage_Contribution_Pages_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Manage_Contribution_Pages_Table_1',
        'label' => E::ts('Manage Contribution Pages Table 1'),
        'saved_search_id.name' => 'Manage_Contribution_Pages',
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
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'sort' => [
            [
              'is_active',
              'DESC',
            ],
            [
              'title',
              'ASC',
            ],
          ],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'title',
              'dataType' => 'String',
              'label' => E::ts('Title'),
              'sortable' => TRUE,
              'cssRules' => [
                [
                  'disabled',
                  'is_active',
                  '=',
                  FALSE,
                ],
              ],
            ],
            [
              'type' => 'field',
              'key' => 'id',
              'dataType' => 'Integer',
              'label' => E::ts('ID'),
              'sortable' => TRUE,
              'cssRules' => [
                [
                  'disabled',
                  'is_active',
                  '=',
                  FALSE,
                ],
              ],
            ],
            [
              'type' => 'field',
              'key' => 'is_active',
              'dataType' => 'Boolean',
              'label' => E::ts('Enabled?'),
              'sortable' => TRUE,
              'editable' => TRUE,
              'cssRules' => [
                [
                  'disabled',
                  'is_active',
                  '=',
                  FALSE,
                ],
              ],
            ],
            [
              'type' => 'field',
              'key' => 'financial_type_id:label',
              'dataType' => 'Integer',
              'label' => E::ts('Financial Type'),
              'sortable' => TRUE,
              'cssRules' => [
                [
                  'disabled',
                  'is_active',
                  '=',
                  FALSE,
                ],
              ],
            ],
            [
              'text' => E::ts('Links'),
              'style' => 'default',
              'size' => 'btn-xs',
              'icon' => 'fa-bars',
              'links' => [
                [
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '_blank',
                  'icon' => 'fa-external-link',
                  'text' => E::ts('Live Page'),
                  'style' => 'default',
                  'path' => 'civicrm/contribute/transact?reset=1&id=[id]',
                  'condition' => [],
                ],
                [
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '_blank',
                  'icon' => 'fa-external-link',
                  'text' => E::ts('Test-drive'),
                  'style' => 'default',
                  'path' => 'civicrm/contribute/transact?reset=1&id=[id]&action=preview',
                  'condition' => [],
                ],
              ],
              'type' => 'menu',
              'alignment' => 'text-right',
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                  'icon' => 'fa-search',
                  'text' => E::ts('Find Contributions'),
                  'style' => 'default',
                  'path' => 'civicrm/contribute/search?contribution_page_id=[id]&force=1&reset=1',
                  'condition' => [],
                ],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => 'ContributionPage',
                  'action' => 'update',
                  'join' => '',
                  'target' => '',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'path' => '',
                  'condition' => [],
                ],
                [
                  'path' => 'civicrm/admin/contribute/manage?action=copy&gid=[id]',
                  'csrf' => 'qfKey',
                  'icon' => 'fa-clone',
                  'text' => E::ts('Clone'),
                  'style' => 'secondary',
                  'condition' => [],
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                ],
                [
                  'task' => 'enable',
                  'entity' => 'ContributionPage',
                  'target' => 'crm-popup',
                  'icon' => 'fa-toggle-on',
                  'text' => E::ts('Enable'),
                  'style' => 'default',
                  'condition' => [],
                ],
                [
                  'task' => 'disable',
                  'entity' => 'ContributionPage',
                  'target' => 'crm-popup',
                  'icon' => 'fa-toggle-off',
                  'text' => E::ts('Disable'),
                  'style' => 'default',
                  'condition' => [],
                ],
                [
                  'entity' => 'ContributionPage',
                  'action' => '',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete'),
                  'style' => 'danger',
                  'path' => '',
                  'condition' => [],
                  'task' => 'delete',
                ],
              ],
              'type' => 'menu',
              'icon' => 'fa-bars',
              'alignment' => 'text-right',
            ],
          ],
          'button' => NULL,
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
