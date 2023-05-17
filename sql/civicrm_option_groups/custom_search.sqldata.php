<?php
return CRM_Core_CodeGen_OptionGroup::create('custom_search', 'a/0023')
  ->addMetadata([
    'title' => ts('Custom Search'),
  ])
  ->addValues(['label', 'name', 'value', 'weight', 'description'], [
    ['CRM_Contact_Form_Search_Custom_Sample', 'CRM_Contact_Form_Search_Custom_Sample', 1, 1, ts('Household Name and State')],
    ['CRM_Contact_Form_Search_Custom_ContributionAggregate', 'CRM_Contact_Form_Search_Custom_ContributionAggregate', 2, 2, ts('Contribution Aggregate')],
    ['CRM_Contact_Form_Search_Custom_Group', 'CRM_Contact_Form_Search_Custom_Group', 4, 4, ts('Include / Exclude Search')],
    ['CRM_Contact_Form_Search_Custom_PostalMailing', 'CRM_Contact_Form_Search_Custom_PostalMailing', 5, 5, ts('Postal Mailing')],
    ['CRM_Contact_Form_Search_Custom_Proximity', 'CRM_Contact_Form_Search_Custom_Proximity', 6, 6, ts('Proximity Search')],
    ['CRM_Contact_Form_Search_Custom_EventAggregate', 'CRM_Contact_Form_Search_Custom_EventAggregate', 7, 7, ts('Event Aggregate')],
    ['CRM_Contact_Form_Search_Custom_ActivitySearch', 'CRM_Contact_Form_Search_Custom_ActivitySearch', 8, 8, ts('Activity Search'), 'is_active' => 0],
    ['CRM_Contact_Form_Search_Custom_PriceSet', 'CRM_Contact_Form_Search_Custom_PriceSet', 9, 9, ts('Price Set Details for Event Participants')],
    ['CRM_Contact_Form_Search_Custom_ZipCodeRange', 'CRM_Contact_Form_Search_Custom_ZipCodeRange', 10, 10, ts('Zip Code Range')],
    ['CRM_Contact_Form_Search_Custom_DateAdded', 'CRM_Contact_Form_Search_Custom_DateAdded', 11, 11, ts('Date Added to CiviCRM')],
    ['CRM_Contact_Form_Search_Custom_MultipleValues', 'CRM_Contact_Form_Search_Custom_MultipleValues', 12, 12, ts('Custom Group Multiple Values Listing')],
    ['CRM_Contact_Form_Search_Custom_ContribSYBNT', 'CRM_Contact_Form_Search_Custom_ContribSYBNT', 13, 13, ts('Contributions made in Year X and not Year Y')],
    ['CRM_Contact_Form_Search_Custom_TagContributions', 'CRM_Contact_Form_Search_Custom_TagContributions', 14, 14, ts('Find Contribution Amounts by Tag')],
    ['CRM_Contact_Form_Search_Custom_FullText', 'CRM_Contact_Form_Search_Custom_FullText', 15, 15, ts('Full-text Search')],
  ]);
