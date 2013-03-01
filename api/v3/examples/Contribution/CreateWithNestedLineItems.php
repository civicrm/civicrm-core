<?php

/*
 Create Contribution with Nested Line Items
 */
function contribution_create_example(){
$params = array( 
  'contact_id' => 1,
  'receive_date' => '20120511',
  'total_amount' => '100',
  'financial_type_id' => 1,
  'payment_instrument_id' => 1,
  'non_deductible_amount' => '10',
  'fee_amount' => '50',
  'net_amount' => '90',
  'trxn_id' => 12345,
  'invoice_id' => 67890,
  'source' => 'SSF',
  'contribution_status_id' => 1,
  'version' => 3,
  'skipLineItem' => 1,
  'api.line_item.create' => array( 
      '0' => array( 
          'price_field_id' => 1,
          'qty' => 2,
          'line_total' => '20',
          'unit_price' => '10',
        ),
      '1' => array( 
          'price_field_id' => 1,
          'qty' => 1,
          'line_total' => '80',
          'unit_price' => '80',
        ),
    ),
);

  $result = civicrm_api( 'contribution','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function contribution_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'contact_id' => '1',
          'financial_type_id' => '1',
          'contribution_page_id' => '',
          'payment_instrument_id' => '1',
          'receive_date' => '20120511000000',
          'non_deductible_amount' => '10',
          'total_amount' => '100',
          'fee_amount' => '50',
          'net_amount' => '90',
          'trxn_id' => '12345',
          'invoice_id' => '67890',
          'currency' => 'USD',
          'cancel_date' => '',
          'cancel_reason' => '',
          'receipt_date' => '',
          'thankyou_date' => '',
          'source' => 'SSF',
          'amount_level' => '',
          'contribution_recur_id' => '',
          'honor_contact_id' => '',
          'is_test' => '',
          'is_pay_later' => '',
          'contribution_status_id' => '1',
          'honor_type_id' => '',
          'address_id' => '',
          'check_number' => 'null',
          'campaign_id' => '',
          'api.line_item.create' => array( 
              '0' => array( 
                  'is_error' => 0,
                  'version' => 3,
                  'count' => 1,
                  'id' => 1,
                  'values' => array( 
                      '0' => array( 
                          'id' => '1',
                          'entity_table' => 'civicrm_contribution',
                          'entity_id' => '1',
                          'price_field_id' => '1',
                          'label' => 'line item',
                          'qty' => '2',
                          'unit_price' => '10',
                          'line_total' => '20',
                          'participant_count' => '',
                          'price_field_value_id' => '',
                          'financial_type_id' => '',
                          'deductible_amount' => '',
                        ),
                    ),
                ),
              '1' => array( 
                  'is_error' => 0,
                  'version' => 3,
                  'count' => 1,
                  'id' => 2,
                  'values' => array( 
                      '0' => array( 
                          'id' => '2',
                          'entity_table' => 'civicrm_contribution',
                          'entity_id' => '1',
                          'price_field_id' => '1',
                          'label' => 'line item',
                          'qty' => '1',
                          'unit_price' => '80',
                          'line_total' => '80',
                          'participant_count' => '',
                          'price_field_value_id' => '',
                          'financial_type_id' => '',
                          'deductible_amount' => '',
                        ),
                    ),
                ),
            ),
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateContributionChainedLineItems and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ContributionTest.php
*
* You can see the outcome of the API tests at
* http://tests.dev.civicrm.org/trunk/results-api_v3
*
* To Learn about the API read
* http://book.civicrm.org/developer/current/techniques/api/
*
* and review the wiki at
* http://wiki.civicrm.org/confluence/display/CRMDOC/CiviCRM+Public+APIs
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/