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
  *
  * @group headless
  */
class CRM_Contact_Form_Task_PrintMailingLabelTest extends CiviUnitTestCase {

  /**
   * Test the mailing label rows contain the primary addresses when location_type_id = none (as primary) is chosen in form.
   *
   * core/issue-1158:
   */
  public function testMailingLabel(): void {
    $contactIDs = [
      $this->individualCreate(['first_name' => 'Antonia', 'last_name' => 'D`souza']),
      $this->individualCreate(['first_name' => 'Anthony', 'last_name' => 'Collins']),
    ];
    // Disable searchPrimaryDetailsOnly civi settings so we could test the functionality without it.
    Civi::settings()->set('searchPrimaryDetailsOnly', '0');

    $addresses = [];
    // Create non-primary and primary addresses of each contact.
    foreach ($contactIDs as $contactID) {
      // Create the non-primary address first.
      foreach (['non-primary', 'primary'] as $flag) {
        // @TODO: bug - this doesn't affect as if its the first and only address created for a contact then it always consider it as primary
        $isPrimary = ($flag === 'primary');
        $addresses[$contactID][$flag] = $this->callAPISuccess('Address', 'create', [
          'street_name' => 'Main Street',
          'street_number' => '23',
          'street_address' => 'Main Street 23',
          'postal_code' => '6971 BN',
          'country_id' => '1152',
          'city' => 'Brummen',
          // this doesn't affect for non-primary address so we need to call the Address.update API again, see below at L57
          'is_primary' => $isPrimary,
          'contact_id' => $contactID,
          'sequential' => 1,
        ])['values'][0];

        if ($flag === 'non-primary') {
          $addresses[$contactID][$flag] = $this->callAPISuccess('Address', 'create', [
            'is_primary' => $isPrimary,
            'id' => $addresses[$contactID][$flag]['id'],
            'sequential' => 1,
          ])['values'][0];
        }
      }
    }

    $form = $this->getTestForm('CRM_Contact_Form_Search_Basic', ['radio_ts' => 'ts_all'])
      ->addSubsequentForm('CRM_Contact_Form_Task_Label', [
        'label_name' => 3475,
        'location_type_id' => NULL,
        'do_not_mail' => 1,
      ]);
    $form->processForm();
    $rows = $form->getException()->errorData['contactRows'];
    $this->assertEquals('Mr. Antonia J. D`souza II
Main Street 23
Brummen, 6971 BN
NETHERLANDS', $rows[$contactIDs[0]][0]);
    $this->assertEquals('Mr. Anthony J. Collins II
Main Street 23
Brummen, 6971 BN
NETHERLANDS', $rows[$contactIDs[1]][0]);
    foreach ($contactIDs as $contactID) {
      // ensure that the address printed in the mailing labe is always primary if 'location_type_id' - none (as Primary) is chosen
      $this->assertStringContainsString($addresses[$contactID]['primary']['street_address'], $rows[$contactID][0]);
    }

    // restore setting
    Civi::settings()->set('searchPrimaryDetailsOnly', '1');
  }

}
