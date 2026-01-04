<?php
use CRM_Search_ExtensionUtil as E;

return [
  'name' => 'SearchSegment',
  'table' => 'civicrm_search_segment',
  'class' => 'CRM_Search_DAO_SearchSegment',
  'getInfo' => fn() => [
    'title' => E::ts('Search Segment'),
    'title_plural' => E::ts('Search Segments'),
    'description' => E::ts('Data segmentation sets for searches.'),
    'log' => TRUE,
    'icon' => 'fa-object-group',
    'label_field' => 'label',
  ],
  'getIndices' => fn() => [
    'UI_name' => [
      'fields' => [
        'name' => TRUE,
      ],
      'unique' => TRUE,
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('Search Segment ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique SearchSegment ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => E::ts('Search Segment Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Unique name'),
      'input_attrs' => [
        'maxlength' => 255,
      ],
    ],
    'label' => [
      'title' => E::ts('Label'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Label for identifying search segment (will appear as name of calculated field)'),
      'input_attrs' => [
        'maxlength' => 255,
      ],
    ],
    'description' => [
      'title' => E::ts('Description'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => E::ts('Description will appear when selecting SearchSegment in the fields dropdown.'),
      'input_attrs' => [
        'maxlength' => 255,
      ],
    ],
    'entity_name' => [
      'title' => E::ts('Entity'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Entity for which this set is used.'),
      'input_attrs' => [
        'maxlength' => 255,
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Search_BAO_SearchSegment', 'getDAOEntityOptions'],
      ],
    ],
    'items' => [
      'title' => E::ts('Items'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('All items in set'),
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
    ],
  ],
];
