<?php
require_once 'CiviTest/CiviUnitTestCase.php';

/*
 * Ensure we pick the right email addresses when sending email to contacts. 
 */

/**
 * Class CRM_Mailing_BAO_SpoolTest
 */
class CRM_Mailing_BAO_EmailSelectionTest extends CiviUnitTestCase {

  /**
   * @return array
   */
  function get_info() {
    return array(
      'name' => 'Email Selection Test',
      'description' => 'Ensure CiviMail chooses the right email addresses when sending email.',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
  }

  function tearDown() {
    parent::tearDown();
  }


  /**
   * Test the email selection method
   *
   * Make sure the correct email addresses are chosen when
   * using the Location Type and Email Selection Method
   * options.
   * 
   */
  function testSend() {
    // Create three contacts with a variety of home and work
    // email addresses.
    $contact_ids = array();
    $contact_params_1 = array(
      'first_name' => substr(sha1(rand()), 0, 7),
      'last_name' => 'Trombone',
      'api.email.create' => array(
        array(
          'email' => 'contact1-home@example.org',
          'is_primary' => 1,
          'location_type_id' => 1 // home
        ),
        array(
          'email' => 'contact1-work@example.org',
          'location_type_id' => 2 // work 
        )
      ),
      'contact_type' => 'Individual',
    );
    $contact_id_1 = $this->individualCreate( $contact_params_1 );
    $contact_ids[] = $contact_id_1;

    $contact_params_2 = array(
      'first_name' => substr(sha1(rand()), 0, 7),
      'last_name' => 'Trumpet',
      'api.email.create' => array(
        array(
          'email' => 'contact2-home@example.org',
          'is_primary' => 1,
          'location_type_id' => 1 // home
         ),
         array(
           'email' => 'contact2-work@example.org',
           'is_primary' => 0,
           'is_bulkmail' => 1,
           'location_type_id' => 2 // work 
         )
      ),
      'contact_type' => 'Individual',
    );
    $contact_id_2 = $this->individualCreate( $contact_params_2 );
    $contact_ids[] = $contact_id_2;

    $contact_params_3 = array(
      'first_name' => substr(sha1(rand()), 0, 7),
      'last_name' => 'Tuba',
      'api.email.create' => array(
        'email' => 'contact3-home@example.org',
        'is_primary' => 1,
        'location_type_id' => 1 // home
      ),
      'contact_type' => 'Individual',
    );
    $contact_id_3 = $this->individualCreate( $contact_params_3 );
    $contact_ids[] = $contact_id_3;

    // Create a group to be use for creating the mailing.
    $group_params = array(
      'name' => 'mail-test',
      'title' => 'mail-test',
      'group_type' => array(2),
    );
    $group = civicrm_api3('Group', 'create', $group_params);
    $values = array_pop($group['values']);
    $group_id = intval($values['id']);

    // Put all of the newly created contacts in it.
    $group_contact_params = array(
      'group_id' => $group_id
    );
    while(list(,$contact_id) = each($contact_ids)) {
      $group_contact_params['contact_id'] = $contact_id;
      civicrm_api3('GroupContact', 'create', $group_contact_params);
    }
    
    // FIXME why does anthony_anderson@civicrm.org randomly show up in my email 
    // table?
    $sql = "DELETE FROM civicrm_email WHERE email = 'anthony_anderson@civicrm.org'";
    CRM_Core_DAO::executeQuery($sql);

    // Create the mailing.
    $mailing_params = array(
      'name' => 'test mailing',
      'subject' => 'test mailing',
      'created_id' => $contact_id_1, 
    );
    $result = civicrm_api3('Mailing', 'create', $mailing_params);
    $values = array_pop($result['values']);
    $mailing_id = intval($values['id']);

    // Manually insert mailing group relationship
    $sql = "INSERT INTO civicrm_mailing_group VALUES(NULL, %0, 'Include', 'civicrm_group', %1, NULL, NULL)";
    $params = array(
      0 => array($mailing_id, 'Integer'),
      1 => array($group_id, 'Integer'),
    );
    CRM_Core_DAO::executeQuery($sql, $params);

    // Now test... By default, location type is automatic
    // and email selection method is automatic. That means
    // we should get the is_bulkmail email, followed by the
    // is_primary email.
    $emails = $this->getEmails($mailing_id); 
    $expected = array('contact1-home@example.org', 'contact2-work@example.org', 'contact3-home@example.org');
    $this->compare($emails, $expected);

    // Now change the rules and re-test - only work addresses, excluding people
    // who don't have a work address.
    $this->updateMailing($mailing_id, 2, 'location-only');
    $emails = $this->getEmails($mailing_id); 
    $expected = array('contact1-work@example.org', 'contact2-work@example.org');
    $this->compare($emails, $expected);

    // Now, invert. Exclude all work addresses.
    $this->updateMailing($mailing_id, 2, 'location-exclude');
    $emails = $this->getEmails($mailing_id); 
    $expected = array('contact1-home@example.org', 'contact2-home@example.org', 'contact3-home@example.org');
    $this->compare($emails, $expected);

    // Lastly, prefer work addresses
    $this->updateMailing($mailing_id, 2, 'location-prefer');
    $emails = $this->getEmails($mailing_id); 
    $expected = array('contact1-work@example.org', 'contact2-work@example.org', 'contact3-home@example.org');
    $this->compare($emails, $expected);
  }

  /**
   * Compare the arrays of email addresses
   *
   * Handles assertions when testing to see if we got the
   * right list of email addresses.
   */
  private function compare($actual, $expected) {
    $this->assertEquals(count($actual),count($expected), 'Incorrect number of email addresses selected');
    while(list(,$email_address) = each($expected)) {
      $this->assertEquals(in_array($email_address, $actual), TRUE, "Missing expected email address: $email_address");
    }
  }

  /**
   * Get list of email addresses for a mailing
   *
   * For the given mailing_id, populate the civicrm_mailing_recipients table
   * and then return the email addresses that were populated.
   */
  private function getEmails($mailing_id) {
    // job_id is only used to create a unique temp table name
    $job_id = rand();

    // Populate civicrm_mailing_recipients table
    CRM_Mailing_BAO_Mailing::getRecipients($job_id, $mailing_id, NULL, NULL, TRUE);

    // Now fetch results...
    $sql = "SELECT email FROM civicrm_email e JOIN civicrm_mailing_recipients r ON
      e.id = r.email_id WHERE mailing_id = %0";
    $params = array(
      0 => array($mailing_id, 'Integer')
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $emails = array();
    while($dao->fetch()) {
      $emails[] = $dao->email;
    }
    return $emails;
  }

  /**
   * Update the given mailing with the given parameters
   *
   * Helper function to quickly change the mailing to reflect
   * new location_type_id and email_selection_method.
   */
  private function updateMailing($mailing_id, $location_type_id, $email_selection_method) {
    $sql = "UPDATE civicrm_mailing SET location_type_id = %0, 
      email_selection_method = %1 WHERE id = %2";
    $params = array(
      0 => array($location_type_id, 'Integer'),
      1 => array($email_selection_method, 'String'),
      2 => array($mailing_id, 'Integer')
    );
    CRM_Core_DAO::executeQuery($sql,$params);
  }
}


