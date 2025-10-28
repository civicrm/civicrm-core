<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;
use Civi\Api4\AfformSubmission;
use Civi\Api4\Contact;
use Civi\Api4\Phone;

/**
 * Test case for Afform.checkAccess, Afform.prefill and Afform.submit.
 *
 * @group headless
 */
class AfformContactUsageTest extends AfformUsageTestCase {

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    self::$layouts['aboutMe'] = <<<EOHTML
<af-form ctrl="modelListCtrl">
  <af-entity type="Individual" data="{}" name="me" label="Myself" url-autofill="1" autofill="user" />
  <fieldset af-fieldset="me">
      <af-field name="first_name" />
      <af-field name="last_name" />
      <div af-join="Address" min="1" af-repeat="Add">
        <afblock-contact-address></afblock-contact-address>
      </div>
  </fieldset>
</af-form>
EOHTML;
    self::$layouts['registerSite'] = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual', source: 'Register A site'}" url-autofill="1" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="FBAC" />
  <af-entity url-autofill="1" type="Activity" name="Activity1" label="Activity 1" data="{activity_type_id: '1', source_contact_id: 'Individual1'}" actions="{create: true, update: true}" security="FBAC" />
  <fieldset af-fieldset="Individual1">
    <afblock-name-individual></afblock-name-individual>
  </fieldset>
  <fieldset af-fieldset="Activity1">
    <legend class="af-text">Activity 1</legend>
    <af-field name="subject" />
  </fieldset>
</af-form>
EOHTML;
    self::$layouts['employer'] = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual'}" url-autofill="1" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" autofill="user" />
  <af-entity data="{contact_type: 'Organization'}" type="Contact" name="Organization1" label="Organization 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1">
    <legend class="af-text">Individual 1</legend>
    <afblock-name-individual></afblock-name-individual>
    <div af-join="Email" min="1" af-repeat="Add">
      <afblock-contact-email></afblock-contact-email>
    </div>
    <af-field name="employer_id" defn="{input_type: 'Select', input_attrs: {}}" />
  </fieldset>
  <fieldset af-fieldset="Organization1">
    <legend class="af-text">Organization 1</legend>
    <div class="af-container">
      <af-field name="organization_name" />
    </div>
    <div af-join="Email">
      <afblock-contact-email></afblock-contact-email>
    </div>
  </fieldset>
  <button class="af-button btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;
    self::$layouts['updateInfo'] = <<<EOHTML
<af-form ctrl="modelListCtrl">
  <af-entity data="{contact_type: 'Individual', source: 'Update Info'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1">
      <af-field name="first_name" defn="{required: true, input_attrs: {}}" />
      <af-field name="middle_name" />
      <af-field name="last_name" defn="{required: false, input_attrs: {maxlength: 20}}"/>
      <div af-join="Email">
        <div class="af-container af-layout-inline">
          <af-field name="email" />
        </div>
      </div>
  </fieldset>
</af-form>
EOHTML;
  }

  public function testAboutMeAllowed(): void {
    $this->useValues([
      'layout' => self::$layouts['aboutMe'],
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Try creating empty contact: not ok
    $submission = [
      ['fields' => []],
    ];
    $result = Afform::submit()
      ->setName($this->formName)
      ->setValues(['me' => $submission])
      ->execute();
    // Contact not created
    $this->assertEmpty($result[0]['me']);

    // Try creating contact with only first_name: ok
    $submission = [
      ['fields' => ['first_name' => 'Hello']],
    ];
    $result = Afform::submit()
      ->setName($this->formName)
      ->setValues(['me' => $submission])
      ->execute();
    // Contact created
    $this->assertNotEmpty($result[0]['me']);

    $cid = $this->createLoggedInUser();
    \CRM_Core_Config::singleton()->userPermissionTemp = new \CRM_Core_Permission_Temp();

    // Autofill form with current user. See `Civi\Afform\Behavior\ContactAutofill`
    $prefill = Afform::prefill()
      ->setName($this->formName)
      ->setFillMode('form')
      // This should be ignored and not mess up the prefill
      ->setArgs(['dummy_distraction' => ['id' => 1]])
      ->execute()
      ->indexBy('name');
    $this->assertEquals('Logged In', $prefill['me']['values'][0]['fields']['first_name']);
    $this->assertMatchesRegularExpression('/^User/', $prefill['me']['values'][0]['fields']['last_name']);

    $submission = [
      ['fields' => ['first_name' => 'Firsty', 'last_name' => 'Lasty']],
    ];

    Afform::submit()
      ->setName($this->formName)
      ->setValues(['me' => $submission])
      ->execute();

    $contact = Contact::get(FALSE)->addWhere('id', '=', $cid)->execute()->first();
    $this->assertEquals('Firsty', $contact['first_name']);
    $this->assertEquals('Lasty', $contact['last_name']);
  }

  public function testChainSelect(): void {
    $this->useValues([
      'layout' => self::$layouts['aboutMe'],
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Get states for USA
    $result = Afform::getOptions()
      ->setName($this->formName)
      ->setModelName('me')
      ->setFieldName('state_province_id')
      ->setJoinEntity('Address')
      ->setValues(['country_id' => \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Country', 'United States', 'id', 'name')])
      ->execute();
    $this->assertEquals('Alabama', $result[0]['label']);

    // Get states for UK
    $result = Afform::getOptions()
      ->setName($this->formName)
      ->setModelName('me')
      ->setFieldName('state_province_id')
      ->setJoinEntity('Address')
      ->setValues(['country_id' => \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Country', 'United Kingdom', 'id', 'name')])
      ->execute();
    $this->assertEquals('Aberdeen City', $result[0]['label']);
  }

  public function testCheckEntityReferenceFieldsReplacement(): void {
    $this->useValues([
      'layout' => self::$layouts['registerSite'],
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
      'create_submission' => TRUE,
    ]);

    $firstName = uniqid(__FUNCTION__);

    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => $firstName,
            'last_name' => 'site',
            // Not allowed to be updated because it's set in 'data'
            'source' => 'This field is set in the data array',
            // Not allowed to be updated because it's not a field on the form
            'formal_title' => 'Danger this field is not on the form',
          ],
        ],
      ],
      'Activity1' => [
        [
          'fields' => [
            'subject' => 'Individual1',
          ],
        ],
      ],
    ];
    Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();

    $submission = AfformSubmission::get(FALSE)
      ->addOrderBy('id', 'DESC')
      ->execute()->first();

    $this->assertEquals($this->formName, $submission['afform_name']);
    $this->assertIsInt($submission['data']['Activity1'][0]['id']);
    $this->assertEquals('Individual1', $submission['data']['Activity1'][0]['fields']['subject']);
    $this->assertIsInt($submission['data']['Individual1'][0]['id']);
    $this->assertEquals($firstName, $submission['data']['Individual1'][0]['fields']['first_name']);
    $this->assertEquals('site', $submission['data']['Individual1'][0]['fields']['last_name']);
    $this->assertEquals('This field is set in the data array', $submission['data']['Individual1'][0]['fields']['source']);

    // Check that Activity was submitted correctly.
    $activity = \Civi\Api4\Activity::get(FALSE)
      ->addWhere('id', '=', $submission['data']['Activity1'][0]['id'])
      ->execute()->first();
    $this->assertEquals('Individual1', $activity['subject']);
    $contact = \Civi\Api4\Contact::get()
      ->addWhere('id', '=', $submission['data']['Individual1'][0]['id'])
      ->execute()->first();
    $this->assertEquals($firstName, $contact['first_name']);
    $this->assertEquals('site', $contact['last_name']);
    // Check that the data overrides form submission
    $this->assertEquals('Register A site', $contact['source']);
    // Check that the contact and the activity were correctly linked up as per the form.
    $this->getTestRecord('ActivityContact', ['contact_id' => $contact['id'], 'activity_id' => $activity['id']]);
  }

  public function testCheckAccess(): void {
    $this->useValues([
      'layout' => self::$layouts['aboutMe'],
      'permission' => ['access CiviCRM'],
    ]);
    $this->createLoggedInUser();
    \CRM_Core_Config::singleton()->userPermissionTemp = NULL;
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access Contact Dashboard',
    ];
    $check = Afform::checkAccess()
      ->addValue('name', $this->formName)
      ->setAction('get')
      ->execute()->first();
    $this->assertFalse($check['access']);
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
    ];
    $check = Afform::checkAccess()
      ->addValue('name', $this->formName)
      ->setAction('get')
      ->execute()->first();
    $this->assertTrue($check['access']);
  }

  public function testAboutMeForbidden(): void {
    $this->useValues([
      'layout' => self::$layouts['aboutMe'],
      'permission' => \CRM_Core_Permission::ALWAYS_DENY_PERMISSION,
    ]);

    $this->createLoggedInUser();
    \CRM_Core_Config::singleton()->userPermissionTemp = new \CRM_Core_Permission_Temp();

    try {
      Afform::prefill()
        ->setName($this->formName)
        ->setFillMode('form')
        ->setArgs([])
        ->execute()
        ->indexBy('name');
      $this->fail('Expected authorization exception from Afform.prefill');
    }
    catch (\CRM_Core_Exception $e) {
      // Should fail permission check
    }
    $this->assertTrue(is_a($e, '\Civi\API\Exception\UnauthorizedException'));

    try {
      Afform::submit()
        ->setName($this->formName)
        ->setArgs([])
        ->setValues([
          'does.n' => 'tmatter',
        ])
        ->execute();
      $this->fail('Expected authorization exception from Afform.submit');
    }
    catch (\CRM_Core_Exception $e) {
      // Should fail permission check
    }
    $this->assertTrue(is_a($e, '\Civi\API\Exception\UnauthorizedException'));
  }

  public function testEmployerReference(): void {
    $this->useValues([
      'layout' => self::$layouts['employer'],
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $firstName = uniqid(__FUNCTION__);
    $orgName = uniqid(__FUNCTION__);

    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => $firstName,
            'last_name' => 'employee',
            // Selecting the Org entity as employer of the Individual
            'employer_id' => 'Organization1',
          ],
        ],
      ],
      'Organization1' => [
        [
          'fields' => [
            'organization_name' => $orgName,
          ],
        ],
      ],
    ];
    Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();
    $contact = \Civi\Api4\Contact::get()
      ->addWhere('first_name', '=', $firstName)
      ->addWhere('last_name', '=', 'employee')
      ->addJoin('Contact AS org', 'LEFT', ['employer_id', '=', 'org.id'])
      ->addSelect('org.organization_name')
      ->execute()->first();
    $this->assertEquals($orgName, $contact['org.organization_name']);
  }

  public function testEmptyEmployerReference(): void {
    $this->useValues([
      'layout' => self::$layouts['employer'],
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $firstName = uniqid(__FUNCTION__);
    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => $firstName,
            'last_name' => 'non-employee',
            // This should result in a NULL value because organization_name is left blank
            'employer_id' => 'Organization1',
          ],
        ],
      ],
      'Organization1' => [
        [
          'fields' => [
            'organization_name' => '',
          ],
        ],
      ],
    ];
    Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();
    $contact = \Civi\Api4\Contact::get()
      ->addWhere('first_name', '=', $firstName)
      ->addWhere('last_name', '=', 'non-employee')
      ->addSelect('employer_id')
      ->execute()->first();
    $this->assertNull($contact['employer_id']);
  }

  public function testCreatingContactsWithOnlyEmail(): void {
    $this->useValues([
      'layout' => self::$layouts['employer'],
      'create_submission' => TRUE,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $individualEmail = uniqid('individual@') . '.test';
    $orgEmail = uniqid('org@') . '.test';
    $locationType = \CRM_Core_BAO_LocationType::getDefault()->id;
    $values = [
      'Individual1' => [
        [
          'fields' => [
            'employer_id' => 'Organization1',
          ],
          'joins' => [
            'Email' => [
              ['email' => $individualEmail, 'location_type_id' => $locationType, 'is_primary' => TRUE],
            ],
          ],
        ],
      ],
      'Organization1' => [
        [
          'fields' => [],
          'joins' => [
            'Email' => [
              ['email' => $orgEmail, 'location_type_id' => $locationType, 'is_primary' => TRUE],
            ],
          ],
        ],
      ],
    ];
    Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();
    $contact = \Civi\Api4\Contact::get()
      ->addWhere('display_name', '=', $individualEmail)
      ->addJoin('Contact AS org', 'LEFT', ['employer_id', '=', 'org.id'])
      ->addSelect('display_name', 'org.display_name', 'org.id', 'email_primary')
      ->execute()->first();
    $this->assertEquals($orgEmail, $contact['org.display_name']);

    $submission = \Civi\Api4\AfformSubmission::get(FALSE)
      ->addOrderBy('id', 'DESC')
      ->setLimit(1)
      ->execute()->single();
    $this->assertEquals($contact['id'], $submission['data']['Individual1'][0]['id']);
    $this->assertEquals($contact['org.id'], $submission['data']['Organization1'][0]['id']);
    $this->assertEquals('Organization1', $submission['data']['Individual1'][0]['fields']['employer_id']);
    $this->assertEquals($contact['email_primary'], $submission['data']['Individual1'][0]['joins']['Email'][0]['id']);
    $this->assertEquals($individualEmail, $submission['data']['Individual1'][0]['joins']['Email'][0]['email']);
    $this->assertEquals($locationType, $submission['data']['Individual1'][0]['joins']['Email'][0]['location_type_id']);
    $this->assertEquals($orgEmail, $submission['data']['Organization1'][0]['joins']['Email'][0]['email']);
    $this->assertEquals($locationType, $submission['data']['Organization1'][0]['joins']['Email'][0]['location_type_id']);
  }

  public function testDedupeIndividual(): void {
    $layout = <<<EOHTML
<af-form ctrl="modelListCtrl">
  <af-entity type="Contact" data="{contact_type: 'Individual'}" name="Individual1" contact-dedupe="Individual.Supervised" />
  <fieldset af-fieldset="Individual1">
      <af-field name="first_name" />
      <af-field name="middle_name" />
      <af-field name="last_name" />
      <div af-join="Email" min="1" af-repeat="Add">
        <afblock-contact-email></afblock-contact-email>
      </div>
  </fieldset>
</af-form>
EOHTML;
    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $lastName = uniqid(__FUNCTION__);
    $contact = $this->createTestRecord('Individual', [
      'first_name' => 'Bob',
      'last_name' => $lastName,
      'email_primary.email' => '123@example.com',
    ]);

    $locationType = \CRM_Core_BAO_LocationType::getDefault()->id;
    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => 'Bob',
            'middle_name' => 'New',
            'last_name' => $lastName,
          ],
          'joins' => [
            'Email' => [
              ['email' => '123@example.com', 'location_type_id' => $locationType, 'is_primary' => TRUE],
            ],
          ],
        ],
      ],
    ];
    Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();

    // Check that the contact was updated per dedupe rule
    $result = \Civi\Api4\Contact::get(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->execute()->single();
    $this->assertEquals('New', $result['middle_name']);
  }

  public function testFormValidationEntityFields(): void {
    $this->useValues([
      'layout' => self::$layouts['updateInfo'],
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $values = [
      'Individual1' => [
        [
          'fields' => [],
          'joins' => [
            'Email' => [
              ['email' => 'test@example.org'],
              [],
            ],
          ],
        ],
      ],
    ];

    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues($values)
        ->execute();
      $this->fail('Should have thrown exception');
    }
    catch (\CRM_Core_Exception $e) {
      // Should fail required fields missing
      $this->assertStringContainsString('First Name is a required field', $e->getMessage());
      $this->assertStringContainsString('Email is a required field', $e->getMessage());
    }

  }

  public function testFormValidationMaxlength(): void {
    $this->useValues([
      'layout' => self::$layouts['updateInfo'],
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => 'Bob',
            'last_name' => str_repeat('Too Long', 3),
          ],
          'joins' => [
            'Email' => [
              ['email' => 'test@example.org'],
            ],
          ],
        ],
      ],
    ];

    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues($values)
        ->execute();
      $this->fail('Should have thrown exception');
    }
    catch (\CRM_Core_Exception $e) {
      // Should fail required fields missing
      $this->assertEquals('Last Name has a max length of 20.', $e->getMessage());
    }
  }

  public function testFormValidationEntityJoinFields(): void {
    $this->useValues([
      'layout' => self::$layouts['updateInfo'],
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
          ],
          'joins' => [
            'Email' => [[]],
          ],
        ],
      ],
    ];

    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues($values)
        ->execute();
      $this->fail('Should have thrown exception');
    }
    catch (\CRM_Core_Exception $e) {
      // Should fail required fields missing
      $this->assertEquals('Email is a required field.', $e->getMessage());
    }

  }

  public function testSubmissionLimit() {
    $this->useValues([
      'layout' => self::$layouts['aboutMe'],
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
      'create_submission' => TRUE,
      'submit_limit_per_user' => 3,
      'submit_limit' => 5,
    ]);

    $cid = $this->createLoggedInUser();
    \CRM_Core_Config::singleton()->userPermissionTemp = new \CRM_Core_Permission_Temp();

    $submitValues = [
      ['fields' => ['first_name' => 'Firsty', 'last_name' => 'Lasty']],
    ];

    // Submit twice
    Afform::submit()
      ->setName($this->formName)
      ->setValues(['me' => $submitValues])
      ->execute();
    Afform::submit()
      ->setName($this->formName)
      ->setValues(['me' => $submitValues])
      ->execute();

    // Submit draft - won't count toward the limit
    Afform::submitDraft()
      ->setName($this->formName)
      ->setValues(['me' => []])
      ->execute();

    // Autofilling form works because limit hasn't been reached
    Afform::prefill()
      ->setName($this->formName)
      ->setFillMode('form')
      ->execute();

    // Submit again (this will overwrite the draft)
    Afform::submit()
      ->setName($this->formName)
      ->setValues(['me' => $submitValues])
      ->execute();

    // Stats should report that we've reached the per-user limit
    $stats = Afform::get()
      ->addSelect('submit_enabled', 'submission_count', 'submit_currently_open')
      ->addWhere('name', '=', $this->formName)
      ->execute()->single();
    $this->assertTrue($stats['submit_enabled']);
    $this->assertFalse($stats['submit_currently_open']);
    $this->assertEquals(3, $stats['submission_count']);

    // Prefilling and submitting are no longer allowed.
    try {
      Afform::prefill()
        ->setName($this->formName)
        ->setFillMode('entity')
        ->execute();
      $this->fail();
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
    }
    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues(['me' => $submitValues])
        ->execute();
      $this->fail();
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
    }
    $this->assertTrue(is_a($e, '\Civi\API\Exception\UnauthorizedException'));

    // Switch users to test the total limit
    $this->userLogout();
    $this->createLoggedInUser();

    $submitValues = [
      ['fields' => ['first_name' => 'Secondy', 'last_name' => 'Lasty']],
    ];
    Afform::submit()
      ->setName($this->formName)
      ->setValues(['me' => $submitValues])
      ->execute();

    // Submit draft - won't count toward the limit
    Afform::submitDraft()
      ->setName($this->formName)
      ->setValues(['me' => []])
      ->execute();

    // Autofilling form works because limit hasn't been reached
    Afform::prefill()
      ->setName($this->formName)
      ->setFillMode('form')
      ->execute();

    // Submit again (this will overwrite the draft)
    Afform::submit()
      ->setName($this->formName)
      ->setValues(['me' => $submitValues])
      ->execute();

    // Stats should report that we've reached the total submission limit
    $stats = Afform::get()
      ->addSelect('submit_enabled', 'submission_count', 'submit_currently_open')
      ->addWhere('name', '=', $this->formName)
      ->execute()->single();
    $this->assertTrue($stats['submit_enabled']);
    $this->assertFalse($stats['submit_currently_open']);
    $this->assertEquals(5, $stats['submission_count']);

    // Prefilling and submitting are no longer allowed.
    try {
      Afform::prefill()
        ->setName($this->formName)
        ->setFillMode('entity')
        ->execute();
      $this->fail();
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
    }
    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues(['me' => $submitValues])
        ->execute();
      $this->fail();
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
    }
    $this->assertTrue(is_a($e, '\Civi\API\Exception\UnauthorizedException'));
  }

  public function testQuickAddWithDataValues(): void {
    $contactType = $this->createTestRecord('ContactType', [
      'parent_id:name' => 'Individual',
    ])['name'];

    $html = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" data="{contact_sub_type: ['$contactType']}" name="me" label="Myself" url-autofill="1" autofill="user" />
  <fieldset af-fieldset="me">
      <af-field name="id" />
      <af-field name="first_name" />
      <af-field name="last_name" />
  </fieldset>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $html,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $lastName = uniqid(__FUNCTION__);

    // We're not submitting the above form, we're creating a 'quick-add' Individual, e.g. from the "Existing Contact" popup
    Afform::submit()
      ->setName('afformQuickAddIndividual')
      ->setValues([
        'Individual1' => [
          [
            'fields' => ['first_name' => 'Jane', 'last_name' => $lastName],
          ],
        ],
      ])
      ->execute();
    // This first submit we did not specify a parent form, so got a generic individual
    $contact = $this->getTestRecord('Individual', ['first_name' => 'Jane', 'last_name' => $lastName]);
    $this->assertNull($contact['contact_sub_type']);

    // Now specify the above form as the parent

    // We're not submitting the above form, we're creating a 'quick-add' Individual, e.g. from the "Existing Contact" popup
    Afform::submit()
      ->setName('afformQuickAddIndividual')
      ->setValues([
        'Individual1' => [
          [
            'fields' => ['first_name' => 'John', 'last_name' => $lastName],
          ],
        ],
      ])
      ->setArgs([
        'parentFormName' => "afform:$this->formName",
        'parentFormFieldName' => "me:id",
      ])
      ->execute();
    // This first submit we did not specify a parent form, so got a generic individual
    $contact = $this->getTestRecord('Individual', ['first_name' => 'John', 'last_name' => $lastName]);
    $this->assertEquals([$contactType], $contact['contact_sub_type']);
  }

  public function testMultipleLocationJoins(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" url-autofill="1" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <div actions="{update: true, delete: true}" class="af-container">
      <div class="af-container af-layout-inline">
        <af-field name="first_name" />
        <af-field name="last_name" />
      </div>
    </div>
    <div af-join="Phone" actions="{update: true, delete: true}" data="{location_type_id: 1}">
      <div class="af-container af-layout-inline">
        <af-field name="phone" defn="{required: false, input_attrs: {}}" />
        <af-field name="phone_ext" />
      </div>
    </div>
    <div af-join="Phone" actions="{update: true, delete: true}" data="{location_type_id: 2}">
      <div class="af-container af-layout-inline">
        <af-field name="phone" defn="{required: false, input_attrs: {}}" />
      </div>
    </div>
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()" ng-if="afform.showSubmitButton">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $cid = $this->createTestRecord('Individual', [
      'first_name' => 'One',
      'last_name' => 'Name',
    ])['id'];

    $phone2 = $this->createTestRecord('Phone', [
      'phone' => '2-2',
      'location_type_id' => 2,
      'phone_ext' => '222',
      'contact_id' => $cid,
    ])['id'];

    $prefill = Afform::prefill()
      ->setName($this->formName)
      ->setFillMode('form')
      ->setArgs(['Individual1' => $cid])
      ->execute()
      ->indexBy('name');

    $this->assertCount(1, $prefill['Individual1']['values']);
    $this->assertEquals('One', $prefill['Individual1']['values'][0]['fields']['first_name']);
    $this->assertNull($prefill['Individual1']['values'][0]['joins']['Phone'][0]);
    $this->assertEquals('2-2', $prefill['Individual1']['values'][0]['joins']['Phone'][1]['phone']);

    // Create one new phone, update the other
    $submitValues = [
      [
        'fields' => ['first_name' => 'Firsty', 'last_name' => 'Lasty'],
        'joins' => [
          'Phone' => [
            ['phone' => '1-1-1', 'phone_ext' => '111'],
            ['id' => $phone2, 'phone' => '2-2-2'],
          ],
        ],
      ],
    ];
    $submit = Afform::submit()
      ->setName($this->formName)
      ->setArgs(['Individual1' => $cid])
      ->setValues(['Individual1' => $submitValues])
      ->execute();

    $phoneValues = Phone::get(FALSE)
      ->addWhere('contact_id', '=', $cid)
      ->addOrderBy('location_type_id')
      ->execute();

    $this->assertCount(2, $phoneValues);
    $this->assertEquals(1, $phoneValues[0]['location_type_id']);
    $this->assertEquals('1-1-1', $phoneValues[0]['phone']);
    $this->assertEquals('111', $phoneValues[0]['phone_ext']);
    $this->assertEquals(2, $phoneValues[1]['location_type_id']);
    $this->assertEquals('2-2-2', $phoneValues[1]['phone']);
    $this->assertEquals('222', $phoneValues[1]['phone_ext']);

    // Create a new contact with no phones
    $cid = $this->createTestRecord('Individual', [
      'first_name' => 'Two',
      'last_name' => 'Name',
    ])['id'];

    // Create one new phone, update the other
    $submitValues = [
      [
        'fields' => ['first_name' => 'Two', 'last_name' => 'Lasty'],
        'joins' => [
          'Phone' => [
            ['phone' => '1', 'phone_ext' => '111'],
            ['phone' => '2'],
          ],
        ],
      ],
    ];
    $submit = Afform::submit()
      ->setName($this->formName)
      ->setArgs(['Individual1' => $cid])
      ->setValues(['Individual1' => $submitValues])
      ->execute();

    $phoneValues = Phone::get(FALSE)
      ->addWhere('contact_id', '=', $cid)
      ->addOrderBy('location_type_id')
      ->execute();

    $this->assertCount(2, $phoneValues);
    $this->assertEquals(1, $phoneValues[0]['location_type_id']);
    $this->assertEquals('1', $phoneValues[0]['phone']);
    $this->assertEquals('111', $phoneValues[0]['phone_ext']);
    $this->assertEquals(2, $phoneValues[1]['location_type_id']);
    $this->assertEquals('2', $phoneValues[1]['phone']);

    $prefill = Afform::prefill()
      ->setName($this->formName)
      ->setFillMode('form')
      ->setArgs(['Individual1' => $cid])
      ->execute()
      ->indexBy('name');

    $this->assertCount(1, $prefill['Individual1']['values']);
    $this->assertEquals('Two', $prefill['Individual1']['values'][0]['fields']['first_name']);
    $this->assertEquals('1', $prefill['Individual1']['values'][0]['joins']['Phone'][0]['phone']);
    $this->assertEquals($phoneValues[0]['id'], $prefill['Individual1']['values'][0]['joins']['Phone'][0]['id']);
    $this->assertEquals('111', $prefill['Individual1']['values'][0]['joins']['Phone'][0]['phone_ext']);
    $this->assertEquals('2', $prefill['Individual1']['values'][0]['joins']['Phone'][1]['phone']);
    $this->assertEquals($phoneValues[1]['id'], $prefill['Individual1']['values'][0]['joins']['Phone'][1]['id']);

  }

}
