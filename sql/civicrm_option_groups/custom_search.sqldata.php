<?php
return CRM_Core_CodeGen_OptionGroup::create('custom_search', 'a/0023')
  ->addMetadata([
    'title' => ts('Custom Search'),
  ])
  ->addValues([
    [
      'value' => 1,
      'name' => 'CRM_Contact_Form_Search_Custom_Sample',
      'weight' => 1,
      'description' => ts('Household Name and State'),
    ],
    [
      'value' => 2,
      'name' => 'CRM_Contact_Form_Search_Custom_ContributionAggregate',
      'weight' => 2,
      'description' => ts('Contribution Aggregate'),
    ],
    [
      'value' => 4,
      'name' => 'CRM_Contact_Form_Search_Custom_Group',
      'weight' => 4,
      'description' => ts('Include / Exclude Search'),
    ],
    [
      'value' => 5,
      'name' => 'CRM_Contact_Form_Search_Custom_PostalMailing',
      'weight' => 5,
      'description' => ts('Postal Mailing'),
    ],
    [
      'value' => 6,
      'name' => 'CRM_Contact_Form_Search_Custom_Proximity',
      'weight' => 6,
      'description' => ts('Proximity Search'),
    ],
    [
      'value' => 7,
      'name' => 'CRM_Contact_Form_Search_Custom_EventAggregate',
      'weight' => 7,
      'description' => ts('Event Aggregate'),
    ],
    [
      'value' => 8,
      'name' => 'CRM_Contact_Form_Search_Custom_ActivitySearch',
      'weight' => 8,
      'description' => ts('Activity Search'),
      'is_active' => 0,
    ],
    [
      'value' => 9,
      'name' => 'CRM_Contact_Form_Search_Custom_PriceSet',
      'weight' => 9,
      'description' => ts('Price Set Details for Event Participants'),
    ],
    [
      'value' => 10,
      'name' => 'CRM_Contact_Form_Search_Custom_ZipCodeRange',
      'weight' => 10,
      'description' => ts('Zip Code Range'),
    ],
    [
      'value' => 11,
      'name' => 'CRM_Contact_Form_Search_Custom_DateAdded',
      'weight' => 11,
      'description' => ts('Date Added to CiviCRM'),
    ],
    [
      'value' => 12,
      'name' => 'CRM_Contact_Form_Search_Custom_MultipleValues',
      'weight' => 12,
      'description' => ts('Custom Group Multiple Values Listing'),
    ],
    [
      'value' => 13,
      'name' => 'CRM_Contact_Form_Search_Custom_ContribSYBNT',
      'weight' => 13,
      'description' => ts('Contributions made in Year X and not Year Y'),
    ],
    [
      'value' => 14,
      'name' => 'CRM_Contact_Form_Search_Custom_TagContributions',
      'weight' => 14,
      'description' => ts('Find Contribution Amounts by Tag'),
    ],
    [
      'value' => 15,
      'name' => 'CRM_Contact_Form_Search_Custom_FullText',
      'weight' => 15,
      'description' => ts('Full-text Search'),
    ],
  ])
  ->syncColumns('fill', ['name' => 'label']);
