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

  private string $mailingFormat;

  public function setUp(): void {
    $this->mailingFormat = Civi::settings()->get('mailing_format') ?? '';
    Civi::settings()->set('mailing_format', $this->getDefaultMailingFormat());
    parent::setUp();
  }

  public function tearDown(): void {
    Civi::settings()->set('mailing_format', $this->mailingFormat);
    Civi::settings()->set('searchPrimaryDetailsOnly', TRUE);
    parent::tearDown();
  }

  /**
   * Get the mailing format that we use as a baseline for these tests.
   *
   * This is currently also the setting default but that could change and the tests
   * are not testing that.
   *
   * @return string
   */
  protected function getDefaultMailingFormat(): string {
    return "{contact.addressee}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.supplemental_address_3}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}";
  }

  /**
   * Test tokens are rendered in the mailing labels when declared via deprecated hooks.
   */
  public function testMailingLabelTokens(): void {
    \Civi::settings()->set('mailing_format', $this->getDefaultMailingFormat() . ' {test.last_initial}');
    $this->hookClass->setHook('civicrm_tokenValues', [$this, 'hookTokenValues']);
    $this->hookClass->setHook('civicrm_tokens', [$this, 'hook_tokens']);
    $this->createTestAddresses();
    $rows = $this->submitForm([]);
    $this->assertCount(2, $rows);
    $this->assertEquals($this->getExpectedAddress('collins') . ' C', $rows[$this->ids['Contact']['collins']][0]);
    $this->assertEquals($this->getExpectedAddress('souza') . ' S', $rows[$this->ids['Contact']['souza']][0]);
  }

  /**
   * Test the mailing label rows contain the primary addresses when
   * location_type_id = none (as primary) is chosen in form.
   *
   * core/issue-1158:
   */
  public function testMailingLabel(): void {
    $addresses = $this->createTestAddresses();
    // Disable searchPrimaryDetailsOnly civi settings so we could test the functionality without it.
    Civi::settings()->set('searchPrimaryDetailsOnly', '0');

    $submitValues = [
      'location_type_id' => NULL,
      'do_not_mail' => 1,
    ];
    $rows = $this->submitForm($submitValues);
    $this->assertEquals($this->getExpectedAddress('souza'), $rows[$this->ids['Contact']['souza']][0]);
    $this->assertEquals($this->getExpectedAddress('collins'), $rows[$this->ids['Contact']['collins']][0]);
    foreach ([$this->ids['Contact']['souza'], $this->ids['Contact']['collins']] as $contactID) {
      // ensure that the address printed in the mailing label is always primary if 'location_type_id' - none (as Primary) is chosen
      $this->assertStringContainsString($addresses[$contactID]['primary']['street_address'], $rows[$contactID][0]);
    }
  }

  /**
   * Get the default address we expect for our test contact with the default string.
   *
   * @param string $identifier
   *
   * @return string
   */
  protected function getExpectedAddress(string $identifier): string {
    if ($identifier === 'souza') {
      return 'Mr. Antonia J. D`souza II
Main Street 231
Brummen, 6971 BN
NETHERLANDS';
    }
    if ($identifier === 'collins') {
      return 'Mr. Anthony J. Collins II
Main Street 231
Brummen, 6971 BN
NETHERLANDS';
    }
    return '';
  }

  /**
   * @return array
   */
  public function createTestAddresses(): array {
    $contactIDs = [
      $this->individualCreate([
        'first_name' => 'Antonia',
        'last_name' => 'D`souza',
        'middle_name' => 'J.',
      ], 'souza'),
      $this->individualCreate([
        'first_name' => 'Anthony',
        'last_name' => 'Collins',
        'middle_name' => 'J.',
      ], 'collins'),
    ];
    $addresses = [];
    // Create non-primary and primary addresses of each contact.
    foreach ($contactIDs as $contactID) {
      // Create the non-primary address first.
      foreach (['non-primary', 'primary'] as $flag) {
        $isPrimary = ($flag === 'primary');
        $addresses[$contactID][$flag] = $this->createTestEntity('Address', [
          'street_name' => 'Main Street',
          'street_number' => '23' . (int) $isPrimary,
          'street_address' => 'Main Street 23' . (int) $isPrimary,
          'postal_code' => '6971 BN',
          'country_id' => '1152',
          'city' => 'Brummen',
          // this doesn't affect for non-primary address so we need to call the Address.update API again, see below at L57
          'is_primary' => $isPrimary,
          'contact_id' => $contactID,
        ]);
      }
    }
    return $addresses;
  }

  /**
   * Implement token values hook.
   *
   * @param array $details
   */
  public function hookTokenValues(array &$details): void {
    foreach ($details as $index => $detail) {
      $details[$index]['last_initial'] = str_contains($detail['display_name'], 'souza') ? 'S' : 'C';
    }
  }

  /**
   * Implements civicrm_tokens().
   */
  public function hook_tokens(&$tokens): void {
    $tokens['test'] = ['last_initial' => 'last_initial'];
  }

  /**
   * @param array $submitValues
   *
   * @return array
   */
  public function submitForm(array $submitValues): array {
    $criteria = ['radio_ts' => 'ts_all', ['contact_id', 'IN', $this->ids['Contact']]];
    $form = $this->getTestForm('CRM_Contact_Form_Search_Basic', $criteria)
      ->addSubsequentForm('CRM_Contact_Form_Task_Label',
        [
          'label_name' => 3475,
        ]
        + $submitValues);
    $form->processForm();
    return $form->getException()->errorData['contactRows'];
  }

}
