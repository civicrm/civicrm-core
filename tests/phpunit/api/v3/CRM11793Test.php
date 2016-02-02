<?php
/**
 *  File for the CRM11793 issue
 *  Include class definitions
 */

/**
 *  Test APIv3 civicrm_activity_* functions
 *
 * @package   CiviCRM
 */
class api_v3_CRM11793Test extends CiviUnitTestCase {

  /**
   * Test setup for every test.
   *
   * Connect to the database, truncate the tables that will be used
   * and redirect stdin to a temporary file
   */
  public function setUp() {
    //  Connect to the database
    parent::setUp();

    require_once 'CiviTest/Contact.php';

    // lets create one contact of each type
    Contact::createIndividual();
    Contact::createHousehold();
    Contact::createOrganisation();
  }

  public function tearDown() {
  }

  /**
   * Test civicrm_contact_create.
   *
   * Verify that attempt to create individual contact with only
   * first and last names succeeds
   */
  public function testCRM11793Organization() {
    $this->_testCRM11793ContactType('Organization');
  }

  public function testCRM11793Household() {
    $this->_testCRM11793ContactType('Household');
  }

  public function testCRM11793Individual() {
    $this->_testCRM11793ContactType('Individual');
  }

  /**
   * @param $contactType
   */
  public function _testCRM11793ContactType($contactType) {
    $result = $this->callAPISuccess(
      'contact',
      'get',
      array(
        'contact_type' => $contactType,
      )
    );

    foreach ($result['values'] as $idx => $contact) {
      $this->assertEquals($contact['contact_type'], $contactType, "In line " . __LINE__);
    }
  }

}
