<?php

return [
  'name' => 'Campaign',
  'table' => 'civicrm_campaign',
  'class' => 'CRM_Campaign_DAO_Campaign',
  'getInfo' => fn() => [
    'title' => ts('Campaign'),
    'title_plural' => ts('Campaigns'),
    'description' => ts('Campaigns link activities, contributions, mailings, etc. that share a programmatic goal.'),
    'add' => '3.3',
    'icon' => 'fa-bullhorn',
    'label_field' => 'title',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/campaign/add?reset=1',
    'update' => 'civicrm/campaign/add?reset=1&action=update&id=[id]',
    'delete' => 'civicrm/campaign/add?reset=1&action=delete&id=[id]',
  ],
  'getIndices' => fn() => [
    'UI_name' => [
      'fields' => [
        'name' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '5.63',
    ],
    'index_campaign_type_id' => [
      'fields' => [
        'campaign_type_id' => TRUE,
      ],
      'add' => '5.63',
    ],
    'index_status_id' => [
      'fields' => [
        'status_id' => TRUE,
      ],
      'add' => '5.63',
    ],
    'UI_external_identifier' => [
      'fields' => [
        'external_identifier' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '3.3',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Campaign ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique Campaign ID.'),
      'add' => '3.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('ID'),
      ],
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Campaign Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Name of the Campaign.'),
      'add' => '3.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Name'),
      ],
    ],
    'title' => [
      'title' => ts('Campaign Title'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Title of the Campaign.'),
      'add' => '3.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Title'),
      ],
    ],
    'description' => [
      'title' => ts('Campaign Description'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Full description of Campaign.'),
      'add' => '3.3',
      'input_attrs' => [
        'rows' => 8,
        'cols' => 60,
        'label' => ts('Description'),
      ],
    ],
    'start_date' => [
      'title' => ts('Campaign Start Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('Date and time that Campaign starts.'),
      'add' => '3.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Start Date'),
        'format_type' => 'activityDateTime',
      ],
    ],
    'end_date' => [
      'title' => ts('Campaign End Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('Date and time that Campaign ends.'),
      'add' => '3.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('End Date'),
        'format_type' => 'activityDateTime',
      ],
    ],
    'campaign_type_id' => [
      'title' => ts('Campaign Type'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Campaign Type ID.Implicit FK to civicrm_option_value where option_group = campaign_type'),
      'add' => '3.3',
      'default' => NULL,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Type'),
      ],
      'pseudoconstant' => [
        'option_group_name' => 'campaign_type',
      ],
    ],
    'status_id' => [
      'title' => ts('Campaign Status'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Campaign status ID.Implicit FK to civicrm_option_value where option_group = campaign_status'),
      'add' => '3.3',
      'default' => NULL,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Status'),
      ],
      'pseudoconstant' => [
        'option_group_name' => 'campaign_status',
      ],
    ],
    'external_identifier' => [
      'title' => ts('Campaign External ID'),
      'sql_type' => 'varchar(32)',
      'input_type' => 'Text',
      'description' => ts('Unique trusted external ID (generally from a legacy app/datasource). Particularly useful for deduping operations.'),
      'add' => '3.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('External ID'),
      ],
    ],
    'parent_id' => [
      'title' => ts('Parent Campaign ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Optional parent id for this Campaign.'),
      'add' => '3.3',
      'default' => NULL,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Parent Campaign'),
      ],
      'entity_reference' => [
        'entity' => 'Campaign',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'is_active' => [
      'title' => ts('Is Campaign Active?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this Campaign enabled or disabled/cancelled?'),
      'add' => '3.3',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'created_id' => [
      'title' => ts('Created By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to civicrm_contact, who created this Campaign.'),
      'add' => '3.3',
      'default_callback' => ['CRM_Core_Session', 'getLoggedInContactID'],
      'input_attrs' => [
        'label' => ts('Created By'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'created_date' => [
      'title' => ts('Campaign Created Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'required' => TRUE,
      'readonly' => TRUE,
      'description' => ts('Date and time that Campaign was created.'),
      'add' => '3.3',
      'default' => 'CURRENT_TIMESTAMP',
      'input_attrs' => [
        'format_type' => 'activityDateTime',
        'label' => ts('Created Date'),
      ],
    ],
    'last_modified_id' => [
      'title' => ts('Modified By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to civicrm_contact, who recently edited this Campaign.'),
      'add' => '3.3',
      'default_callback' => ['CRM_Core_Session', 'getLoggedInContactID'],
      'input_attrs' => [
        'label' => ts('Modified By'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'last_modified_date' => [
      'title' => ts('Campaign Modified Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('Date and time that Campaign was edited last time.'),
      'required' => TRUE,
      'readonly' => TRUE,
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'add' => '3.3',
    ],
    'goal_general' => [
      'title' => ts('Campaign Goals'),
      'sql_type' => 'text',
      'input_type' => 'RichTextEditor',
      'description' => ts('General goals for Campaign.'),
      'add' => '3.4',
    ],
    'goal_revenue' => [
      'title' => ts('Goal Revenue'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => 'Text',
      'description' => ts('The target revenue for this campaign.'),
      'add' => '3.4',
      'input_attrs' => [
        'label' => ts('Goal Revenue'),
      ],
    ],
  ],
];
