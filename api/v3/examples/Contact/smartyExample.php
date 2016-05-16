<?php
// $Id$

/*
 * disabled for now while I check out security issues
 */



/*
 demonstrates use of smarty as output
 */
function contact_get_example() {
  $params = array(
    'id' => 1,
    'version' => 3,
    'api_Contribution_get' => array(),
    'sequential' => 1,
    'format.smarty' => 'api/v3/exampleLetter.tpl',
  );

  require_once 'api/api.php';
  $result = civicrm_api('contact', 'get', $params);

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function contact_get_expectedresult() {

  $expectedResult = '


Dear abc3 xyz3

You have made 2 payments to us so far. You are well on your way
to a complementary free beer.


    USD  100.00  Friday, January  1, 2010
    USD  120.00  Saturday, January  1, 2011
';

  return $expectedResult;
}




/*
* This example has been generated from the API test suite. The test that created it is called
* 
* testGetIndividualWithChainedArraysFormats and can be found in 
* http://svn.civicrm.org/civicrm/branches/v3.4/tests/phpunit/CiviTest/api/v3ContactTest.php
* 
* You can see the outcome of the API tests at 
* http://tests.dev.civicrm.org/trunk/results-api_v3
* and review the wiki at
* http://wiki.civicrm.org/confluence/display/CRMDOC/CiviCRM+Public+APIs
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*/

