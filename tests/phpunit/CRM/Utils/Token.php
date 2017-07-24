<?php

/**
 * Class CRM_Utils_TokenTest
 * @group headless
 */
class CRM_Utils_TokenTest extends CiviUnitTestCase {

  /**
   * Basic test on getTokenDetails function.
   */
  public function testGetTokenDetails() {
    $contactID = $this->individualCreate(array('preferred_communication_method' => array('Phone', 'Fax')));
    $resolvedTokens = CRM_Utils_Token::getTokenDetails(array($contactID));
    $this->assertEquals('Phone, Fax', $resolvedTokens[0][$contactID]['preferred_communication_method']);
  }

  /**
   * Test getting contacts w/o primary location type
   *
   * Check for situation described in CRM-19876.
   */
  public function testSearchByPrimaryLocation() {
    // disable searchPrimaryDetailsOnly civi settings so we could test the functionality without it
    Civi::settings()->set('searchPrimaryDetailsOnly', '0');

    // create a contact with multiple email address and among which one is primary
    $contactID = $this->individualCreate();
    $primaryEmail = uniqid() . '@primary.com';
    $this->callAPISuccess('Email', 'create', array(
      'contact_id' => $contactID,
      'email' => $primaryEmail,
      'location_type_id' => 'Other',
      'is_primary' => 1,
    ));
    $this->callAPISuccess('Email', 'create', array(
      'contact_id' => $contactID,
      'email' => uniqid() . '@galaxy.com',
      'location_type_id' => 'Work',
      'is_primary' => 0,
    ));
    $this->callAPISuccess('Email', 'create', array(
      'contact_id' => $contactID,
      'email' => uniqid() . '@galaxy.com',
      'location_type_id' => 'Work',
      'is_primary' => 0,
    ));

    // when we are fetching contact details NOT ON basis of primary address fields
    $extraParams = array('search_by_primary_details_only' => FALSE);
    $contactIDs = array($contactID);
    $contactDetails = CRM_Utils_Token::getTokenDetails($contactIDs, NULL, TRUE, TRUE, $extraParams);
    $this->assertNotEquals($primaryEmail, $contactDetails[0][$contactID]['email']);

    // when we are fetching contact details ON basis of primary address fields
    $contactDetails = CRM_Utils_Token::getTokenDetails($contactIDs);
    $this->assertEquals($primaryEmail, $contactDetails[0][$contactID]['email']);

    // restore setting
    Civi::settings()->set('searchPrimaryDetailsOnly', '1');
  }

}
