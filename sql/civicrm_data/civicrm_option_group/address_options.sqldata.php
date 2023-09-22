<?php
return CRM_Core_CodeGen_OptionGroup::create('address_options', 'a/0021')
  ->addMetadata([
    'title' => ts('Addressing Options'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Street Address'), 'street_address', 1],
    [ts('Supplemental Address 1'), 'supplemental_address_1', 2],
    [ts('Supplemental Address 2'), 'supplemental_address_2', 3],
    [ts('Supplemental Address 3'), 'supplemental_address_3', 4],
    [ts('City'), 'city', 5],
    [ts('Postal Code'), 'postal_code', 6],
    [ts('Postal Code Suffix'), 'postal_code_suffix', 7],
    [ts('County'), 'county', 8],
    [ts('State/Province'), 'state_province', 9],
    [ts('Country'), 'country', 10],
    [ts('Latitude'), 'geo_code_1', 11],
    [ts('Longitude'), 'geo_code_2', 12],
    [ts('Address Name'), 'address_name', 13],
    [ts('Street Address Parsing'), 'street_address_parsing', 14],
  ]);
