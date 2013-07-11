<?php
/**
 *  File for the CRM11793 issue
 *  Include class definitions
 */
require_once 'CiviTest/CiviUnitTestCase.php';


/**
 *  Test APIv3 civicrm_activity_* functions
 *
 *  @package   CiviCRM
 */
class api_v3_CRM11793Test extends CiviUnitTestCase {
  /**
   *  Constructor
   *
   *  Initialize configuration
   */
  function __construct() {
    parent::__construct();
  }

  /**
   *  Test setup for every test
   *
   *  Connect to the database, truncate the tables that will be used
   *  and redirect stdin to a temporary file
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

  function tearDown() {
  }

  /**
   *  Test civicrm_contact_create
   *
   *  Verify that attempt to create individual contact with only
   *  first and last names succeeds
   */
  function testCRM11793Organization() {
    $this->_testCRM11793ContactType('Organization');
  }

  function testCRM11793Household() {
    $this->_testCRM11793ContactType('Household');
  }
  function testCRM11793Individual() {
    $this->_testCRM11793ContactType('Individual');
  }

  function _testCRM11793ContactType($contactType) {
    $result = civicrm_api(
      'contact',
      'get',
      array(
        'version' => 3,
        'contact_type' => $contactType
      )
    );

    $this->assertAPISuccess($result, "In line " . __LINE__);
    foreach ($result['values'] as $idx => $contact) {
      $this->assertEquals($contact['contact_type'], $contactType, "In line " . __LINE__);
    }
  }
}