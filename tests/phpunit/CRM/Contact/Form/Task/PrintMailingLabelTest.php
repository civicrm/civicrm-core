<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

 /**
  * Test class for CRM_Contact_Form_Task_Label.
  * @group headless
  */
class CRM_Contact_Form_Task_PrintMailingLabelTest extends CiviUnitTestCase {

  protected $_contactIds = NULL;

  protected function setUp() {
    parent::setUp();
    $this->_contactIds = [
      $this->individualCreate(['first_name' => 'Antonia', 'last_name' => 'D`souza']),
      $this->individualCreate(['first_name' => 'Anthony', 'last_name' => 'Collins']),
    ];
  }

  /**
   * core/issue-1158: Test the mailing label rows contain the primary addresses when location_type_id = none (as primary) is chosen in form
   */
  public function testMailingLabel() {
    // Disable searchPrimaryDetailsOnly civi settings so we could test the functionality without it.
    Civi::settings()->set('searchPrimaryDetailsOnly', '0');

    $addresses = [];
    // create non-primary and primary addresses of each contact
    foreach ($this->_contactIds as $contactID) {
      // create the non-primary address first
      foreach (['non-primary', 'primary'] as $flag) {
        // @TODO: bug - this doesn't affect as if its the first and only address created for a contact then it always consider it as primary
        $isPrimary = ($flag == 'primary');
        $streetName = substr(sha1(rand()), 0, 7);
        $addresses[$contactID][$flag] = $this->callAPISuccess('Address', 'create', [
          'street_name' => $streetName,
          'street_number' => '23',
          'street_address' => "$streetName 23",
          'postal_code' => '6971 BN',
          'country_id' => '1152',
          'city' => 'Brummen',
          // this doesn't affect for non-primary address so we need to call the Address.update API again, see below at L57
          'is_primary' => $isPrimary,
          'contact_id' => $contactID,
          'sequential' => 1,
        ])['values'][0];

        if ($flag == 'non-primary') {
          $addresses[$contactID][$flag] = $this->callAPISuccess('Address', 'create', [
            'is_primary' => $isPrimary,
            'id' => $addresses[$contactID][$flag]['id'],
            'sequential' => 1,
          ])['values'][0];
        }
      }
    }

    $form = new CRM_Contact_Form_Task_Label();
    $form->_contactIds = $this->_contactIds;
    $params = [
      'label_name' => 3475,
      'location_type_id' => NULL,
      'do_not_mail' => 1,
      'is_unit_testing' => 1,
    ];
    $rows = $form->postProcess($params);

    foreach ($this->_contactIds as $contactID) {
      // ensure that the address printed in the mailing labe is always primary if 'location_type_id' - none (as Primary) is chosen
      $this->assertContains($addresses[$contactID]['primary']['street_address'], $rows[$contactID][0]);
    }

    // restore setting
    Civi::settings()->set('searchPrimaryDetailsOnly', '1');
  }

}
