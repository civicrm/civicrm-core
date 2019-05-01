<?php

/**
 * Class CRM_Contact_BAO_IndividualTest
 * @group headless
 */
class CRM_Contact_BAO_IndividualTest extends CiviUnitTestCase {

  /**
   * Test case for format() with "null" value dates.
   *
   * See CRM-19123: Merging contacts: blank date fields write as 1970
   */
  public function testFormatNullDates() {
    $params = array(
      'contact_type' => 'Individual',
      'birth_date' => 'null',
      'deceased_date' => 'null',
    );
    $contact = new CRM_Contact_DAO_Contact();

    CRM_Contact_BAO_Individual::format($params, $contact);

    $this->assertEmpty($contact->birth_date);
    $this->assertEmpty($contact->deceased_date);
  }

  /**
   *  Test case to check the formatting of the Display name and Sort name
   *  Standard formatting is assumed.
   */
  public function testFormatDisplayName() {

    $params = array(
      'contact_type' => 'Individual',
      'first_name' => 'Ben',
      'last_name' => 'Lee',
      'individual_prefix' => 'Mr.',
      'individual_suffix' => 'Jr.',
    );

    $contact = new CRM_Contact_DAO_Contact();

    CRM_Contact_BAO_Individual::format($params, $contact);

    $this->assertEquals("Mr. Ben Lee Jr.", $contact->display_name);
    $this->assertEquals("Lee, Ben", $contact->sort_name);
  }

  /**
   *  Testing the use of adding prefix and suffix by id.
   *  Standard Prefixes and Suffixes are assumed part of
   *  the test database
   */
  public function testFormatDisplayNamePrefixesById() {

    $params = array(
      'contact_type' => 'Individual',
      'first_name' => 'Ben',
      'last_name' => 'Lee',
      // this is the doctor
      'prefix_id' => 4,
      // and the doctor is a senior
      'suffix_id' => 2,
    );

    $contact = new CRM_Contact_DAO_Contact();

    CRM_Contact_BAO_Individual::format($params, $contact);

    $this->assertEquals("Dr. Ben Lee Sr.", $contact->display_name);
  }

  /**
   *  Testing the use of adding prefix and suffix by id.
   *  Standard Prefixes and Suffixes are assumed part of
   *  the test database
   */
  public function testFormatDisplayNameNoIndividual() {

    $params = array(
      'contact_type' => 'Organization',
      'first_name' => 'Ben',
      'last_name' => 'Lee',
    );

    $contact = new CRM_Contact_DAO_Contact();

    CRM_Contact_BAO_Individual::format($params, $contact);

    $this->assertNotEquals("Ben Lee", $contact->display_name);
  }

  /**
   *  When no first name or last name are defined, the primary email is used
   */
  public function testFormatDisplayNameOnlyEmail() {

    $email['1'] = array('email' => "bleu01@example.com");
    $email['2'] = array('email' => "bleu02@example.com", 'is_primary' => 1);
    $email['3'] = array('email' => "bleu03@example.com");

    $params = array(
      'contact_type' => 'Individual',
      'email' => $email ,
    );

    $contact = new CRM_Contact_DAO_Contact();

    CRM_Contact_BAO_Individual::format($params, $contact);

    $this->assertEquals("bleu02@example.com", $contact->display_name);
    $this->assertEquals("bleu02@example.com", $contact->sort_name);

  }

}
