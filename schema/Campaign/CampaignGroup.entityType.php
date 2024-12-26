<?php

return [
  'name' => 'CampaignGroup',
  'table' => 'civicrm_campaign_group',
  'class' => 'CRM_Campaign_DAO_CampaignGroup',
  'getInfo' => fn() => [
    'title' => ts('Campaign Group'),
    'title_plural' => ts('Campaign Groups'),
    'description' => ts('Campaign Group Details.'),
    'add' => '3.3',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Campaign Group ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Campaign Group id.'),
      'add' => '3.3',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'campaign_id' => [
      'title' => ts('Campaign ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Foreign key to the activity Campaign.'),
      'add' => '3.3',
      'input_attrs' => [
        'label' => ts('Campaign'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_campaign',
        'key_column' => 'id',
        'label_column' => 'title',
        'prefetch' => 'disabled',
      ],
      'entity_reference' => [
        'entity' => 'Campaign',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'group_type' => [
      'title' => ts('Campaign Group Type'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Select',
      'description' => ts('Type of Group.'),
      'default' => NULL,
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'getCampaignGroupTypes'],
      ],
    ],
    'entity_table' => [
      'title' => ts('Entity Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Name of table where item being referenced is stored.'),
      'add' => '3.3',
      'default' => NULL,
    ],
    'entity_id' => [
      'title' => ts('Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Entity id of referenced table.'),
      'add' => '3.3',
      'default' => NULL,
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
  ],
];
