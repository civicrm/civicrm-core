<?php
return CRM_Core_CodeGen_OptionGroup::create('contact_autocomplete_options', 'a/0043')
  ->addMetadata([
    'title' => ts('Autocomplete Contact Search'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Email Address'), 'email', 2],
    [ts('Phone'), 'phone', 3],
    [ts('Street Address'), 'street_address', 4],
    [ts('City'), 'city', 5],
    [ts('State/Province'), 'state_province', 6],
    [ts('Country'), 'country', 7],
    [ts('Postal Code'), 'postal_code', 8],
  ])
  ->syncColumns('fill', ['value' => 'weight']);
