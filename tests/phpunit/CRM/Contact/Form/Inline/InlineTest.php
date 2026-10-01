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

use Civi\Api4\Generic\Result;
use Civi\Test\FormWrapper;

/**
 * Tests for CRM_Contact_Form_Inline inheritors.
 *
 * @group headless
 */
class CRM_Contact_Form_Inline_InlineTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Dave',
      'last_name' => 'Lobo',
    ]);
  }

  /**
   * Helper to retrieve modified timestamp for optimistic locking.
   *
   * @param int|null $contactId
   * @return string
   */
  private function getOplockTs(?int $contactId = NULL): string {
    $contactId = $contactId ?? $this->getTestEntityID('Contact');
    $timestamps = CRM_Contact_BAO_Contact::getTimestamps($contactId);
    return $timestamps['modified_date'];
  }

  /**
   * Common function to retrieve records created/deleted on forms for the test contact.
   *
   * @param string $entity
   * @param array $orderBy
   * @return \Civi\Api4\Generic\Result
   */
  protected function getForContact(string $entity, array $orderBy = []): Result {
    $contactId = $this->getTestEntityID('Contact');
    $whereField = ($entity === 'Contact') ? 'id' : 'contact_id';
    $params = [
      'checkPermissions' => FALSE,
      'where' => [[$whereField, '=', $contactId]],
      'orderBy' => $orderBy,
    ];
    return civicrm_api4($entity, 'get', $params);
  }

  /**
   * Helper to clean up contacts and related entities.
   */
  public function tearDown(): void {
    $this->quickCleanup($this->tablesToCleanUp);
    parent::tearDown();
  }

  /**
   * Test submitting email inline form (add, edit, delete).
   */
  public function testSubmitEmailForm(): void {
    $contactId = $this->getTestEntityID('Contact');

    // Add email via inline form
    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_Email', [
      'oplock_ts' => $this->getOplockTs(),
      'email' => [
        1 => [
          'email' => 'lobo@example.org',
          'location_type_id' => 1,
          'is_primary' => 1,
        ],
        2 => ['email' => ''],
        3 => ['email' => ''],
        4 => ['email' => ''],
        5 => ['email' => ''],
      ],
    ], ['cid' => $contactId]);
    $formWrapper->processForm();

    $emails = $this->getForContact('Email');
    $this->assertCount(1, $emails);
    $this->assertEquals('lobo@example.org', $emails->first()['email']);
    $this->assertEquals(1, $emails->first()['is_primary']);
    $emailId = $emails->first()['id'];

    // Update existing email and add second email
    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_Email', [
      'oplock_ts' => $this->getOplockTs(),
      'email' => [
        1 => [
          'id' => $emailId,
          'email' => 'lobo.updated@example.org',
          'location_type_id' => 1,
          'is_primary' => 1,
        ],
        2 => [
          'email' => 'lobo.secondary@example.org',
          'location_type_id' => 2,
          'is_primary' => 0,
        ],
        3 => ['email' => ''],
        4 => ['email' => ''],
        5 => ['email' => ''],
      ],
    ], ['cid' => $contactId]);
    $formWrapper->processForm();

    $emails = $this->getForContact('Email', ['is_primary' => 'DESC']);
    $this->assertCount(2, $emails);
    $this->assertEquals('lobo.updated@example.org', $emails->first()['email']);

    // Delete all emails by clearing values
    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_Email', [
      'oplock_ts' => $this->getOplockTs(),
      'email' => [
        1 => ['email' => '', 'location_type_id' => 1, 'is_primary' => 1],
        2 => ['email' => '', 'location_type_id' => 2, 'is_primary' => 0],
        3 => ['email' => ''],
        4 => ['email' => ''],
        5 => ['email' => ''],
      ],
    ], ['cid' => $contactId]);
    $formWrapper->processForm();

    $this->assertEquals(0, $this->getForContact('Email')->count());
  }

  /**
   * Test validation rule for email inline form.
   */
  public function testEmailValidation(): void {
    // Contact with no name and no email should fail validation
    $namelessContact = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => '',
      'last_name' => '',
    ], 'nameless_contact');
    $namelessContactId = $namelessContact['id'];

    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_Email', [
      'oplock_ts' => $this->getOplockTs($namelessContactId),
      'email' => [
        1 => [
          'email' => '',
          'location_type_id' => 1,
          'is_primary' => 0,
        ],
      ],
    ], ['cid' => $namelessContactId]);
    $formWrapper->processForm(FormWrapper::VALIDATED);

    $this->assertEquals(
      ['email[1][email]' => ts('Contact with no name must have an email.')],
      $formWrapper->getValidationOutput()
    );

    // Contact with multiple primary emails should fail validation
    $contactId = $this->getTestEntityID('Contact');
    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_Email', [
      'oplock_ts' => $this->getOplockTs(),
      'email' => [
        1 => [
          'email' => 'one@example.org',
          'location_type_id' => 1,
          'is_primary' => 1,
        ],
        2 => [
          'email' => 'two@example.org',
          'location_type_id' => 2,
          'is_primary' => 1,
        ],
      ],
    ], ['cid' => $contactId]);
    $formWrapper->processForm(FormWrapper::VALIDATED);

    $this->assertEquals(
      ['email[2][is_primary]' => ts('Only one email can be marked as primary.')],
      $formWrapper->getValidationOutput()
    );
  }

  /**
   * Test phone inline form submission and validation.
   */
  public function testPhoneForm(): void {
    $contactId = $this->getTestEntityID('Contact');

    // Test multiple primaries validation
    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_Phone', [
      'oplock_ts' => $this->getOplockTs(),
      'phone' => [
        1 => [
          'phone' => '111111',
          'location_type_id' => 1,
          'is_primary' => 1,
        ],
        2 => [
          'phone' => '222222',
          'location_type_id' => 2,
          'is_primary' => 1,
        ],
      ],
    ], ['cid' => $contactId]);
    $formWrapper->processForm(FormWrapper::VALIDATED);

    $this->assertEquals(
      ['phone[2][is_primary]' => ts('Only one phone can be marked as primary.')],
      $formWrapper->getValidationOutput()
    );

    // Test successful creation
    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_Phone', [
      'oplock_ts' => $this->getOplockTs(),
      'phone' => [
        1 => [
          'phone' => '555-0100',
          'location_type_id' => 1,
          'phone_type_id' => 1,
          'is_primary' => 1,
        ],
      ],
    ], ['cid' => $contactId]);
    $formWrapper->processForm();

    $phones = $this->getForContact('Phone');
    $this->assertCount(1, $phones);
    $this->assertEquals('555-0100', $phones->first()['phone']);

    // Delete all phones by clearing values
    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_Phone', [
      'oplock_ts' => $this->getOplockTs(),
      'phone' => [
        1 => [
          'phone' => '',
          'location_type_id' => 1,
          'is_primary' => 0,
        ],
        2 => ['phone' => ''],
        3 => ['phone' => ''],
        4 => ['phone' => ''],
        5 => ['phone' => ''],
      ],
    ], ['cid' => $contactId]);
    $formWrapper->processForm();

    $this->assertEquals(0, $this->getForContact('Phone')->count());
  }

  /**
   * Test IM inline form submission and validation.
   */
  public function testIMForm(): void {
    $contactId = $this->getTestEntityID('Contact');

    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_IM', [
      'oplock_ts' => $this->getOplockTs(),
      'im' => [
        1 => [
          'name' => 'ghopper',
          'location_type_id' => 1,
          'provider_id' => 1,
          'is_primary' => 1,
        ],
      ],
    ], ['cid' => $contactId]);
    $formWrapper->processForm();

    $ims = $this->getForContact('Im');
    $this->assertCount(1, $ims);
    $this->assertEquals('ghopper', $ims->first()['name']);

    // Delete all IMs by clearing values
    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_IM', [
      'oplock_ts' => $this->getOplockTs(),
      'im' => [
        1 => [
          'name' => '',
          'location_type_id' => 1,
          'provider_id' => 1,
          'is_primary' => 0,
        ],
        2 => ['name' => ''],
        3 => ['name' => ''],
        4 => ['name' => ''],
        5 => ['name' => ''],
      ],
    ], ['cid' => $contactId]);
    $formWrapper->processForm();

    $this->assertEquals(0, $this->getForContact('Im')->count());
  }

  /**
   * Test OpenID inline form submission and validation.
   */
  public function testOpenIDForm(): void {
    $contactId = $this->getTestEntityID('Contact');

    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_OpenID', [
      'oplock_ts' => $this->getOplockTs(),
      'openid' => [
        1 => [
          'openid' => 'https://timbl.openid.example.org',
          'location_type_id' => 1,
          'is_primary' => 1,
        ],
      ],
    ], ['cid' => $contactId]);
    $formWrapper->processForm();

    $openids = $this->getForContact('OpenID');
    $this->assertCount(1, $openids);
    $this->assertEquals('https://timbl.openid.example.org', $openids->first()['openid']);

    // Delete all OpenIDs by clearing values
    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_OpenID', [
      'oplock_ts' => $this->getOplockTs(),
      'openid' => [
        1 => [
          'openid' => '',
          'location_type_id' => 1,
          'is_primary' => 0,
        ],
        2 => ['openid' => ''],
        3 => ['openid' => ''],
        4 => ['openid' => ''],
        5 => ['openid' => ''],
      ],
    ], ['cid' => $contactId]);
    $formWrapper->processForm();

    $this->assertEquals(0, $this->getForContact('OpenID')->count());
  }

  /**
   * Test Website inline form submission and validation.
   */
  public function testWebsiteForm(): void {
    $contactId = $this->getTestEntityID('Contact');

    // Test duplicate website type validation
    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_Website', [
      'oplock_ts' => $this->getOplockTs(),
      'website' => [
        1 => [
          'url' => 'https://example1.com',
          'website_type_id' => 1,
        ],
        2 => [
          'url' => 'https://example2.com',
          'website_type_id' => 1,
        ],
      ],
    ], ['cid' => $contactId]);
    $formWrapper->processForm(FormWrapper::VALIDATED);

    $this->assertEquals(
      ['website[2][website_type_id]' => ts('Contacts may only have one website of each type at most.')],
      $formWrapper->getValidationOutput()
    );

    // Test successful creation
    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_Website', [
      'oplock_ts' => $this->getOplockTs(),
      'website' => [
        1 => [
          'url' => 'https://example.com',
          'website_type_id' => 1,
        ],
      ],
    ], ['cid' => $contactId]);
    $formWrapper->processForm();

    $websites = $this->getForContact('Website');
    $this->assertCount(1, $websites);
    $this->assertEquals('https://example.com', $websites->first()['url']);

    // Delete all websites by clearing values
    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_Website', [
      'oplock_ts' => $this->getOplockTs(),
      'website' => [
        1 => [
          'url' => '',
          'website_type_id' => 1,
        ],
      ],
    ], ['cid' => $contactId]);
    $formWrapper->processForm();

    $this->assertEquals(0, $this->getForContact('Website')->count());
  }

  /**
   * Test ContactName inline form submission and validation.
   */
  public function testContactNameForm(): void {
    $contactId = $this->getTestEntityID('Contact');

    // Update name
    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_ContactName', [
      'oplock_ts' => $this->getOplockTs(),
      'first_name' => 'Donald',
      'last_name' => 'Greenberg',
    ], ['cid' => $contactId]);
    $formWrapper->processForm();

    $updated = $this->getForContact('Contact')->first();
    $this->assertEquals('Donald', $updated['first_name']);
    $this->assertEquals('Greenberg', $updated['last_name']);

    // Empty name with no email on contact should fail validation
    $namelessContact = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => '',
      'last_name' => '',
    ], 'nameless_contact_2');
    $namelessContactId = $namelessContact['id'];

    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_ContactName', [
      'oplock_ts' => $this->getOplockTs($namelessContactId),
      'first_name' => '',
      'last_name' => '',
    ], ['cid' => $namelessContactId]);
    $formWrapper->processForm(FormWrapper::VALIDATED);

    $this->assertEquals(
      ['last_name' => ts('Contact with no email must have a name.')],
      $formWrapper->getValidationOutput()
    );
  }

  /**
   * Test ContactInfo inline form submission.
   */
  public function testContactInfoForm(): void {
    $contactId = $this->getTestEntityID('Contact');

    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_ContactInfo', [
      'oplock_ts' => $this->getOplockTs(),
      'job_title' => 'Software Director',
    ], ['cid' => $contactId]);
    $formWrapper->processForm();

    $updated = $this->getForContact('Contact')->first();
    $this->assertEquals('Software Director', $updated['job_title']);
  }

  /**
   * Test Demographics inline form submission.
   */
  public function testDemographicsForm(): void {
    $contactId = $this->getTestEntityID('Contact');

    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_Demographics', [
      'oplock_ts' => $this->getOplockTs(),
      'gender_id' => 1,
      'birth_date' => '1951-01-01',
      'is_deceased' => 0,
    ], ['cid' => $contactId]);
    $formWrapper->processForm();

    $updated = $this->getForContact('Contact')->first();
    $this->assertEquals(1, $updated['gender_id']);
    $this->assertEquals('1951-01-01', $updated['birth_date']);
    $this->assertFalse((bool) $updated['is_deceased']);
  }

  /**
   * Test CommunicationPreferences inline form submission.
   */
  public function testCommunicationPreferencesForm(): void {
    $contactId = $this->getTestEntityID('Contact');

    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_CommunicationPreferences', [
      'oplock_ts' => $this->getOplockTs(),
      'privacy' => [
        'do_not_email' => 1,
        'do_not_phone' => 0,
        'do_not_mail' => 0,
        'do_not_sms' => 0,
        'do_not_trade' => 0,
      ],
      'preferred_communication_method' => [1 => 1],
      'communication_style_id' => 1,
    ], ['cid' => $contactId]);
    $formWrapper->processForm();

    $updated = $this->getForContact('Contact')->first();
    $this->assertTrue((bool) $updated['do_not_email']);
  }

  /**
   * Test Address inline form submission.
   */
  public function testAddressForm(): void {
    $contactId = $this->getTestEntityID('Contact');

    $formWrapper = new FormWrapper('CRM_Contact_Form_Inline_Address', [
      'oplock_ts' => $this->getOplockTs(),
      'address' => [
        1 => [
          'street_address' => '100 Innovation Way',
          'city' => 'Metropolis',
          'postal_code' => '90210',
          'location_type_id' => 1,
          'is_primary' => 1,
        ],
      ],
    ], ['cid' => $contactId, 'locno' => 1]);
    $formWrapper->processForm();

    $addresses = $this->getForContact('Address');
    $this->assertCount(1, $addresses);
    $this->assertEquals('100 Innovation Way', $addresses->first()['street_address']);
    $this->assertEquals('Metropolis', $addresses->first()['city']);
  }

}
