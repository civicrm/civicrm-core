<?php

return [
  'name' => 'Country',
  'table' => 'civicrm_country',
  'class' => 'CRM_Core_DAO_Country',
  'getInfo' => fn() => [
    'title' => ts('Country'),
    'title_plural' => ts('Countries'),
    'description' => ts('Countries of the world'),
    'add' => '1.1',
    'icon' => 'fa-globe',
    'label_field' => 'name',
  ],
  'getIndices' => fn() => [
    'UI_name_iso_code' => [
      'fields' => [
        'name' => TRUE,
        'iso_code' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '1.1',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Country ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Country ID'),
      'add' => '1.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Country'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Country Name'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'iso_code' => [
      'title' => ts('Country ISO Code'),
      'sql_type' => 'char(2)',
      'input_type' => 'Text',
      'description' => ts('ISO Code'),
      'add' => '1.1',
    ],
    'country_code' => [
      'title' => ts('Country Phone Prefix'),
      'sql_type' => 'varchar(4)',
      'input_type' => 'Text',
      'description' => ts('National prefix to be used when dialing TO this country.'),
      'add' => '1.1',
    ],
    'address_format_id' => [
      'title' => ts('Address Format ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Foreign key to civicrm_address_format.id.'),
      'add' => '3.2',
      'input_attrs' => [
        'label' => ts('Address Format'),
      ],
      'entity_reference' => [
        'entity' => 'AddressFormat',
        'key' => 'id',
      ],
    ],
    'idd_prefix' => [
      'title' => ts('Outgoing Phone Prefix'),
      'sql_type' => 'varchar(4)',
      'input_type' => 'Text',
      'description' => ts('International direct dialing prefix from within the country TO another country'),
      'add' => '1.1',
    ],
    'ndd_prefix' => [
      'title' => ts('Area Code'),
      'sql_type' => 'varchar(4)',
      'input_type' => 'Text',
      'description' => ts('Access prefix to call within a country to a different area'),
      'add' => '1.1',
    ],
    'region_id' => [
      'title' => ts('World Region ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Foreign key to civicrm_worldregion.id.'),
      'add' => '1.8',
      'localize_context' => 'country',
      'input_attrs' => [
        'label' => ts('World Region'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_worldregion',
        'key_column' => 'id',
        'label_column' => 'name',
      ],
      'entity_reference' => [
        'entity' => 'WorldRegion',
        'key' => 'id',
      ],
    ],
    'is_province_abbreviated' => [
      'title' => ts('Abbreviate Province?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Should state/province be displayed as abbreviation for contacts from this country?'),
      'add' => '3.1',
      'default' => FALSE,
    ],
    'is_active' => [
      'title' => ts('Country Is Active'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this Country active?'),
      'add' => '5.35',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
  ],
];
