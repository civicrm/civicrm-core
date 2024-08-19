<?php

return [
  'name' => 'Address',
  'table' => 'civicrm_address',
  'class' => 'CRM_Core_DAO_Address',
  'getInfo' => fn() => [
    'title' => ts('Address'),
    'title_plural' => ts('Addresses'),
    'description' => ts('Stores the physical street / mailing address. This format should be capable of storing ALL international addresses.'),
    'log' => TRUE,
    'add' => '1.1',
    'icon' => 'fa-map-marker',
  ],
  'getIndices' => fn() => [
    'index_location_type' => [
      'fields' => [
        'location_type_id' => TRUE,
      ],
      'add' => '2.0',
    ],
    'index_is_primary' => [
      'fields' => [
        'is_primary' => TRUE,
      ],
      'add' => '2.0',
    ],
    'index_is_billing' => [
      'fields' => [
        'is_billing' => TRUE,
      ],
      'add' => '2.0',
    ],
    'index_street_name' => [
      'fields' => [
        'street_name' => TRUE,
      ],
      'add' => '1.1',
    ],
    'index_city' => [
      'fields' => [
        'city' => TRUE,
      ],
      'add' => '1.1',
    ],
    'index_geo_code_1_geo_code_2' => [
      'fields' => [
        'geo_code_1' => TRUE,
        'geo_code_2' => TRUE,
      ],
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Address ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique Address ID'),
      'add' => '1.1',
      'unique_name' => 'address_id',
      'usage' => [
        'export',
      ],
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Contact ID'),
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'location_type_id' => [
      'title' => ts('Address Location Type'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Which Location does this address belong to.'),
      'add' => '2.0',
      'pseudoconstant' => [
        'table' => 'civicrm_location_type',
        'key_column' => 'id',
        'label_column' => 'display_name',
      ],
    ],
    'is_primary' => [
      'title' => ts('Is Primary'),
      'sql_type' => 'boolean',
      'input_type' => 'Radio',
      'required' => TRUE,
      'description' => ts('Is this the primary address.'),
      'add' => '2.0',
      'default' => FALSE,
    ],
    'is_billing' => [
      'title' => ts('Is Billing Address'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this the billing address.'),
      'add' => '2.0',
      'default' => FALSE,
    ],
    'street_address' => [
      'title' => ts('Street Address'),
      'sql_type' => 'varchar(96)',
      'input_type' => 'Text',
      'description' => ts('Concatenation of all routable street address components (prefix, street number, street name, suffix, unit number OR P.O. Box). Apps should be able to determine physical location with this data (for mapping, mail delivery, etc.).'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'street_number' => [
      'title' => ts('Street Number'),
      'sql_type' => 'int',
      'input_type' => 'Text',
      'description' => ts('Numeric portion of address number on the street, e.g. For 112A Main St, the street_number = 112.'),
      'add' => '1.1',
      'usage' => [
        'export',
      ],
    ],
    'street_number_suffix' => [
      'title' => ts('Street Number Suffix'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Text',
      'description' => ts('Non-numeric portion of address number on the street, e.g. For 112A Main St, the street_number_suffix = A'),
      'add' => '1.1',
      'usage' => [
        'export',
      ],
    ],
    'street_number_predirectional' => [
      'title' => ts('Street Direction Prefix'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Text',
      'description' => ts('Directional prefix, e.g. SE Main St, SE is the prefix.'),
      'add' => '1.1',
    ],
    'street_name' => [
      'title' => ts('Street Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Actual street name, excluding St, Dr, Rd, Ave, e.g. For 112 Main St, the street_name = Main.'),
      'add' => '1.1',
      'usage' => [
        'export',
      ],
    ],
    'street_type' => [
      'title' => ts('Street Type'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Text',
      'description' => ts('St, Rd, Dr, etc.'),
      'add' => '1.1',
    ],
    'street_number_postdirectional' => [
      'title' => ts('Street Direction Suffix'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Text',
      'description' => ts('Directional prefix, e.g. Main St S, S is the suffix.'),
      'add' => '1.1',
    ],
    'street_unit' => [
      'title' => ts('Street Unit'),
      'sql_type' => 'varchar(16)',
      'input_type' => 'Text',
      'description' => ts('Secondary unit designator, e.g. Apt 3 or Unit # 14, or Bldg 1200'),
      'add' => '1.1',
      'usage' => [
        'export',
      ],
    ],
    'supplemental_address_1' => [
      'title' => ts('Supplemental Address 1'),
      'sql_type' => 'varchar(96)',
      'input_type' => 'Text',
      'description' => ts('Supplemental Address Information, Line 1'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'supplemental_address_2' => [
      'title' => ts('Supplemental Address 2'),
      'sql_type' => 'varchar(96)',
      'input_type' => 'Text',
      'description' => ts('Supplemental Address Information, Line 2'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'supplemental_address_3' => [
      'title' => ts('Supplemental Address 3'),
      'sql_type' => 'varchar(96)',
      'input_type' => 'Text',
      'description' => ts('Supplemental Address Information, Line 3'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'city' => [
      'title' => ts('City'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('City, Town or Village Name.'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'county_id' => [
      'title' => ts('County ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'ChainSelect',
      'description' => ts('Which County does this address belong to.'),
      'add' => '1.1',
      'input_attrs' => [
        'control_field' => 'state_province_id',
        'label' => ts('County'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_county',
        'key_column' => 'id',
        'label_column' => 'name',
        'abbr_column' => 'abbreviation',
        'suffixes' => [
          'label',
          'abbr',
        ],
      ],
      'entity_reference' => [
        'entity' => 'County',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'state_province_id' => [
      'title' => ts('State/Province ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'ChainSelect',
      'description' => ts('Which State_Province does this address belong to.'),
      'add' => '1.1',
      'localize_context' => 'province',
      'input_attrs' => [
        'control_field' => 'country_id',
        'label' => ts('State/Province'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_state_province',
        'key_column' => 'id',
        'label_column' => 'name',
        'abbr_column' => 'abbreviation',
        'suffixes' => [
          'label',
          'abbr',
        ],
      ],
      'entity_reference' => [
        'entity' => 'StateProvince',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'postal_code_suffix' => [
      'title' => ts('Postal Code Suffix'),
      'sql_type' => 'varchar(12)',
      'input_type' => 'Text',
      'description' => ts('Store the suffix, like the +4 part in the USPS system.'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '3',
      ],
    ],
    'postal_code' => [
      'title' => ts('Postal Code'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Store both US (zip5) AND international postal codes. App is responsible for country/region appropriate validation.'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '6',
      ],
    ],
    'usps_adc' => [
      'title' => ts('USPS Code'),
      'sql_type' => 'varchar(32)',
      'input_type' => 'Text',
      'deprecated' => TRUE,
      'description' => ts('USPS Bulk mailing code.'),
      'add' => '1.1',
    ],
    'country_id' => [
      'title' => ts('Country ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Which Country does this address belong to.'),
      'add' => '1.1',
      'localize_context' => 'country',
      'input_attrs' => [
        'label' => ts('Country'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_country',
        'key_column' => 'id',
        'label_column' => 'name',
        'name_column' => 'iso_code',
        'abbr_column' => 'iso_code',
        'suffixes' => [
          'label',
          'abbr',
        ],
      ],
      'entity_reference' => [
        'entity' => 'Country',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'geo_code_1' => [
      'title' => ts('Latitude'),
      'sql_type' => 'double',
      'input_type' => 'Text',
      'description' => ts('Latitude'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '9',
      ],
    ],
    'geo_code_2' => [
      'title' => ts('Longitude'),
      'sql_type' => 'double',
      'input_type' => 'Text',
      'description' => ts('Longitude'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '9',
      ],
    ],
    'manual_geo_code' => [
      'title' => ts('Is Manually Geocoded'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this a manually entered geo code'),
      'add' => '4.3',
      'default' => FALSE,
      'usage' => [
        'export',
      ],
    ],
    'timezone' => [
      'title' => ts('Timezone'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Text',
      'description' => ts('Timezone expressed as a UTC offset - e.g. United States CST would be written as "UTC-6".'),
      'add' => '1.1',
    ],
    'name' => [
      'title' => ts('Address Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'add' => '2.1',
      'unique_name' => 'address_name',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'master_id' => [
      'title' => ts('Master Address ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Address ID'),
      'add' => '3.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Master Address Belongs To'),
      ],
      'entity_reference' => [
        'entity' => 'Address',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
  ],
];
