<?php

return [
  'name' => 'RelationshipCache',
  'table' => 'civicrm_relationship_cache',
  'class' => 'CRM_Contact_DAO_RelationshipCache',
  'metaProvider' => '\Civi\Schema\Entity\RelationshipCacheMetadata',
  'getInfo' => fn() => [
    'title' => ts('Related Contact'),
    'title_plural' => ts('Related Contacts'),
    'description' => ts('The cache permutes information from the relationship table to facilitate querying. Every relationship is mapped to multiple records in the cache. Joins should begin on the near side and extract info from the far side.'),
    'log' => FALSE,
    'add' => '5.29',
    'icon' => 'fa-handshake-o',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/contact/view/rel?cid=[near_contact_id]&action=add&reset=1',
    'view' => 'civicrm/contact/view/rel?action=view&reset=1&cid=[near_contact_id]&id=[relationship_id]',
    'update' => 'civicrm/contact/view/rel?action=update&reset=1&cid=[near_contact_id]&id=[relationship_id]&rtype=[orientation]',
    'delete' => 'civicrm/contact/view/rel?action=delete&reset=1&cid=[near_contact_id]&id=[relationship_id]',
  ],
  'getIndices' => fn() => [
    'UI_relationship' => [
      'fields' => [
        'relationship_id' => TRUE,
        'orientation' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '5.29',
    ],
    'index_nearid_nearrelation' => [
      'fields' => [
        'near_contact_id' => TRUE,
        'near_relation' => TRUE,
      ],
      'add' => '5.29',
    ],
    'index_nearid_farrelation' => [
      'fields' => [
        'near_contact_id' => TRUE,
        'far_relation' => TRUE,
      ],
      'add' => '5.29',
    ],
    'index_near_relation' => [
      'fields' => [
        'near_relation' => TRUE,
      ],
      'add' => '5.29',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Relationship Cache ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Relationship Cache ID'),
      'add' => '5.29',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'relationship_id' => [
      'title' => ts('Relationship ID'),
      'sql_type' => 'int unsigned',
      'input_type' => NULL,
      'required' => TRUE,
      'readonly' => TRUE,
      'description' => ts('id of the relationship (FK to civicrm_relationship.id)'),
      'add' => '5.29',
      'input_attrs' => [
        'label' => ts('Relationship'),
      ],
      'entity_reference' => [
        'entity' => 'Relationship',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'relationship_type_id' => [
      'title' => ts('Relationship Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => NULL,
      'required' => TRUE,
      'readonly' => TRUE,
      'description' => ts('id of the relationship type'),
      'add' => '5.29',
      'input_attrs' => [
        'label' => ts('Relationship Type'),
      ],
      'entity_reference' => [
        'entity' => 'RelationshipType',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'orientation' => [
      'title' => ts('Orientation (a_b or b_a)'),
      'sql_type' => 'char(3)',
      'input_type' => NULL,
      'required' => TRUE,
      'readonly' => TRUE,
      'description' => ts('The cache record is a permutation of the original relationship record. The orientation indicates whether it is forward (a_b) or reverse (b_a) relationship.'),
      'add' => '5.29',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'relationshipOrientation'],
      ],
    ],
    'near_contact_id' => [
      'title' => ts('Contact ID (Near side)'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'readonly' => TRUE,
      'description' => ts('id of the first contact'),
      'add' => '5.29',
      'input_attrs' => [
        'label' => ts('Contact (Near side)'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'near_relation' => [
      'title' => ts('Relationship Name (to related contact)'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'readonly' => TRUE,
      'description' => ts('name for relationship of near_contact to far_contact.'),
      'add' => '5.29',
      'input_attrs' => [
        'label' => ts('Relationship to contact'),
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_PseudoConstant', 'relationshipTypeOptions'],
      ],
    ],
    'far_contact_id' => [
      'title' => ts('Contact ID (Far side)'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'readonly' => TRUE,
      'description' => ts('id of the second contact'),
      'add' => '5.29',
      'input_attrs' => [
        'label' => ts('Contact (Far side)'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'far_relation' => [
      'title' => ts('Relationship Name (from related contact)'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'readonly' => TRUE,
      'description' => ts('name for relationship of far_contact to near_contact.'),
      'add' => '5.29',
      'input_attrs' => [
        'label' => ts('Relationship from contact'),
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_PseudoConstant', 'relationshipTypeOptions'],
      ],
    ],
    'is_active' => [
      'title' => ts('Relationship Is Active'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'readonly' => TRUE,
      'description' => ts('is the relationship active ?'),
      'add' => '5.29',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'start_date' => [
      'title' => ts('Relationship Start Date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('date when the relationship started'),
      'add' => '5.29',
      'unique_name' => 'relationship_start_date',
      'input_attrs' => [
        'format_type' => 'activityDate',
      ],
    ],
    'end_date' => [
      'title' => ts('Relationship End Date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('date when the relationship ended'),
      'add' => '5.29',
      'unique_name' => 'relationship_end_date',
      'input_attrs' => [
        'format_type' => 'activityDate',
      ],
    ],
    'case_id' => [
      'title' => ts('Case ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'readonly' => TRUE,
      'description' => ts('FK to civicrm_case'),
      'add' => '5.44',
      'component' => 'CiviCase',
      'default' => NULL,
      'input_attrs' => [
        'label' => ts('Case'),
      ],
      'entity_reference' => [
        'entity' => 'Case',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
