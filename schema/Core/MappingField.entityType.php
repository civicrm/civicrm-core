<?php

return [
  'name' => 'MappingField',
  'table' => 'civicrm_mapping_field',
  'class' => 'CRM_Core_DAO_MappingField',
  'getInfo' => fn() => [
    'title' => ts('Mapping Field'),
    'title_plural' => ts('Mapping Fields'),
    'description' => ts('Individual field mappings for Mapping'),
    'add' => '1.2',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Mapping Field ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Mapping Field ID'),
      'add' => '1.2',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'mapping_id' => [
      'title' => ts('Mapping ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Mapping to which this field belongs'),
      'add' => '1.2',
      'input_attrs' => [
        'label' => ts('Mapping'),
      ],
      'entity_reference' => [
        'entity' => 'Mapping',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'name' => [
      'title' => ts('Field Name (or unique reference)'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Mapping field key'),
      'add' => '1.2',
    ],
    'contact_type' => [
      'title' => ts('Contact Type'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'description' => ts('Contact Type in mapping'),
      'add' => '1.2',
    ],
    'column_number' => [
      'title' => ts('Column Number to map to'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Column number for mapping set'),
      'add' => '1.2',
    ],
    'location_type_id' => [
      'title' => ts('Location type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Location type of this mapping, if required'),
      'add' => '1.2',
      'input_attrs' => [
        'label' => ts('Location type'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_location_type',
        'key_column' => 'id',
        'label_column' => 'display_name',
      ],
      'entity_reference' => [
        'entity' => 'LocationType',
        'key' => 'id',
      ],
    ],
    'phone_type_id' => [
      'title' => ts('Phone type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('Which type of phone does this number belongs.'),
      'add' => '2.2',
    ],
    'im_provider_id' => [
      'title' => ts('IM provider ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Which type of IM Provider does this name belong.'),
      'add' => '3.0',
      'pseudoconstant' => [
        'option_group_name' => 'instant_messenger_service',
      ],
    ],
    'website_type_id' => [
      'title' => ts('Website type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Which type of website does this site belong'),
      'add' => '3.2',
      'pseudoconstant' => [
        'option_group_name' => 'website_type',
      ],
    ],
    'relationship_type_id' => [
      'title' => ts('Relationship type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Relationship type, if required'),
      'add' => '1.2',
      'input_attrs' => [
        'label' => ts('Relationship type'),
      ],
      'entity_reference' => [
        'entity' => 'RelationshipType',
        'key' => 'id',
      ],
    ],
    'relationship_direction' => [
      'title' => ts('Relationship Direction'),
      'sql_type' => 'varchar(6)',
      'input_type' => 'Text',
      'add' => '1.7',
    ],
    'grouping' => [
      'title' => ts('Field Grouping'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('Used to group mapping_field records into related sets (e.g. for criteria sets in search builder mappings).'),
      'add' => '1.5',
      'default' => 1,
    ],
    'operator' => [
      'title' => ts('Operator'),
      'sql_type' => 'varchar(16)',
      'input_type' => 'Select',
      'description' => ts('SQL WHERE operator for search-builder mapping fields (search criteria).'),
      'add' => '1.5',
      'pseudoconstant' => [
        'callback' => 'CRM_Core_SelectValues::getSearchBuilderOperators',
      ],
    ],
    'value' => [
      'title' => ts('Search builder where clause'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('SQL WHERE value for search-builder mapping fields.'),
      'add' => '1.5',
    ],
  ],
];
