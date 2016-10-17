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

}
