<?php
return [
      [
        'name' => 'SavedSearch_Option_Groups',
        'entity' => 'SavedSearch',
        'cleanup' => 'always',
        'update' => 'unmodified',
        'params' => [
          'version' => 4,
          'values' => [
            'name' => 'Option_Groups',
            'label' => 'Administer Option Groups',
            'form_values' => NULL,
            'mapping_id' => NULL,
            'search_custom_id' => NULL,
            'api_entity' => 'OptionGroup',
            'api_params' => [
              'version' => 4,
              'select' => [
                'title',
                'name',
                'is_reserved',
                'is_active',
                'COUNT(OptionGroup_OptionValue_option_group_id_01.id) AS COUNT_OptionGroup_OptionValue_option_group_id_01_id',
                'edit_link',
              ],
              'orderBy' => [],
              'where' => [],
              'groupBy' => [
                'id',
              ],
              'join' => [
                [
                  'OptionValue AS OptionGroup_OptionValue_option_group_id_01',
                  'LEFT',
                  [
                    'id',
                    '=',
                    'OptionGroup_OptionValue_option_group_id_01.option_group_id',
                  ],
                ],
              ],
              'having' => [],
            ],
            'expires_date' => NULL,
            'description' => NULL,
          ],
        ],
      ],
      [
        'name' => 'SavedSearch_Option_Groups_SearchDisplay_Option_Groups_Table_1',
        'entity' => 'SearchDisplay',
        'cleanup' => 'always',
        'update' => 'unmodified',
        'params' => [
          'version' => 4,
          'values' => [
            'name' => 'Option_Groups_Table_1',
            'label' => 'Table',
            'saved_search_id.name' => 'Option_Groups',
            'type' => 'table',
            'settings' => [
              'description' => '',
              'sort' => [
                [
                  'title',
                  'ASC',
                ],
              ],
              'limit' => 50,
              'pager' => [],
              'placeholder' => 5,
              'columns' => [
                [
                  'type' => 'field',
                  'key' => 'title',
                  'dataType' => 'String',
                  'label' => 'Title',
                  'sortable' => TRUE,
                ],
                [
                  'type' => 'field',
                  'key' => 'name',
                  'dataType' => 'String',
                  'label' => 'Name',
                  'sortable' => TRUE,
                ],
                [
                  'type' => 'field',
                  'key' => 'is_reserved',
                  'dataType' => 'Boolean',
                  'label' => 'Reserved?',
                  'sortable' => TRUE,
                ],
                [
                  'type' => 'field',
                  'key' => 'is_active',
                  'dataType' => 'Boolean',
                  'label' => 'Enabled?',
                  'sortable' => TRUE,
                ],
                [
                  'size' => 'btn-xs',
                  'links' => [
                    [
                      'entity' => '',
                      'action' => '',
                      'join' => '',
                      'target' => '',
                      'icon' => 'fa-external-link',
                      'text' => 'Options ([COUNT_OptionGroup_OptionValue_option_group_id_01_id])',
                      'style' => 'default',
                      'path' => '[edit_link]?reset=1',
                      'condition' => [],
                    ],
                    [
                      'entity' => 'OptionGroup',
                      'action' => 'update',
                      'join' => '',
                      'target' => 'crm-popup',
                      'icon' => 'fa-pencil',
                      'text' => 'Settings',
                      'style' => 'default',
                      'path' => '',
                      'condition' => [],
                    ],
                  ],
                  'type' => 'buttons',
                  'alignment' => 'text-right',
                ],
              ],
              'actions' => FALSE,
              'classes' => [
                'table',
                'table-striped',
              ],
              'addButton' => [
                'path' => 'civicrm/admin/options/add?action=add&reset=1',
                'text' => 'Add Option Group',
                'icon' => 'fa-plus',
              ],
              'cssRules' => [
                [
                  'disabled',
                  'is_active',
                  '=',
                  FALSE,
                ],
              ],
            ],
            'acl_bypass' => FALSE,
          ],
        ],
      ],
];
