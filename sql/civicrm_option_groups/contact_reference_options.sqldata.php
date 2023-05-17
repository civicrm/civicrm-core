<?php
return CRM_Core_CodeGen_OptionGroup::create('contact_reference_options', 'a/0044')
  ->addMetadata([
    'title' => ts('Contact Reference Autocomplete Options'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value', 'weight'], [
    [ts('Email Address'), 'email', 2, 2],
    [ts('Phone'), 'phone', 3, 3],
    [ts('Street Address'), 'street_address', 4, 4],
    [ts('City'), 'city', 5, 5],
    [ts('State/Province'), 'state_province', 6, 6],
    [ts('Country'), 'country', 7, 7],
    [ts('Postal Code'), 'postal_code', 8, 8],
  ]);
