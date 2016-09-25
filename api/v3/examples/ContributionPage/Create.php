<?php
/**
 * Test Generated example demonstrating the ContributionPage.create API.
 *
 * @return array
 *   API result array
 */
function contribution_page_create_example() {
  $params = array(
    'title' => 'Test Contribution Page',
    'financial_type_id' => 1,
    'currency' => 'NZD',
    'goal_amount' => 34567,
    'is_pay_later' => 1,
    'is_monetary' => TRUE,
  );

  try{
    $result = civicrm_api3('ContributionPage', 'create', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    );
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function contribution_page_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => array(
      '1' => array(
        'id' => '1',
        'title' => 'Test Contribution Page',
        'intro_text' => '',
        'financial_type_id' => '1',
        'payment_processor' => '',
        'is_credit_card_only' => '',
        'is_monetary' => '1',
        'is_recur' => '',
        'is_confirm_enabled' => '',
        'recur_frequency_unit' => '',
        'is_recur_interval' => '',
        'is_recur_installments' => '',
        'is_pay_later' => '1',
        'pay_later_text' => '',
        'pay_later_receipt' => '',
        'is_partial_payment' => '',
        'initial_amount_label' => '',
        'initial_amount_help_text' => '',
        'min_initial_amount' => '',
        'is_allow_other_amount' => '',
        'default_amount_id' => '',
        'min_amount' => '',
        'max_amount' => '',
        'goal_amount' => '34567',
        'thankyou_title' => '',
        'thankyou_text' => '',
        'thankyou_footer' => '',
        'is_for_organization' => '',
        'for_organization' => '',
        'is_email_receipt' => '',
        'receipt_from_name' => '',
        'receipt_from_email' => '',
        'cc_receipt' => '',
        'bcc_receipt' => '',
        'receipt_text' => '',
        'is_active' => '1',
        'footer_text' => '',
        'amount_block_is_active' => '',
        'start_date' => '',
        'end_date' => '',
        'created_id' => '',
        'created_date' => '',
        'currency' => 'NZD',
        'campaign_id' => '',
        'is_share' => '',
        'is_billing_required' => '',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateContributionPage"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContributionPageTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
