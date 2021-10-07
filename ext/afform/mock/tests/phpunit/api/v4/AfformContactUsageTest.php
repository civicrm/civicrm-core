<?php

/**
 * Test case for Afform.prefill and Afform.submit.
 *
 * @group headless
 */
class api_v4_AfformContactUsageTest extends api_v4_AfformUsageTestCase {

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    self::$layouts['aboutMe'] = <<<EOHTML
<af-form ctrl="modelListCtrl">
  <af-entity type="Contact" data="{contact_type: 'Individual'}" name="me" label="Myself" url-autofill="1" autofill="user" />
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
  }

  public function testAboutMeAllowed(): void {
    $this->useValues([
      'layout' => self::$layouts['aboutMe'],
      'permission' => CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $cid = $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionTemp = new CRM_Core_Permission_Temp();

    $prefill = Civi\Api4\Afform::prefill()
      ->setName($this->formName)
      ->execute()
      ->indexBy('name');
    $this->assertEquals('Logged In', $prefill['me']['values'][0]['fields']['first_name']);
    $this->assertRegExp('/^User/', $prefill['me']['values'][0]['fields']['last_name']);

    $submission = [
      ['fields' => ['first_name' => 'Firsty', 'last_name' => 'Lasty']],
    ];

    Civi\Api4\Afform::submit()
      ->setName($this->formName)
      ->setValues(['me' => $submission])
      ->execute();

    $contact = Civi\Api4\Contact::get(FALSE)->addWhere('id', '=', $cid)->execute()->first();
    $this->assertEquals('Firsty', $contact['first_name']);
    $this->assertEquals('Lasty', $contact['last_name']);
  }

  public function testChainSelect(): void {
    $this->useValues([
      'layout' => self::$layouts['aboutMe'],
      'permission' => CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Get states for USA
    $result = Civi\Api4\Afform::getOptions()
      ->setName($this->formName)
      ->setModelName('me')
      ->setFieldName('state_province_id')
      ->setJoinEntity('Address')
      ->setValues(['country_id' => CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Country', 'United States', 'id', 'name')])
      ->execute();
    $this->assertEquals('Alabama', $result[0]['label']);

    // Get states for UK
    $result = Civi\Api4\Afform::getOptions()
      ->setName($this->formName)
      ->setModelName('me')
      ->setFieldName('state_province_id')
      ->setJoinEntity('Address')
      ->setValues(['country_id' => CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Country', 'United Kingdom', 'id', 'name')])
      ->execute();
    $this->assertEquals('Aberdeen City', $result[0]['label']);
  }

  public function testCheckEntityReferenceFieldsReplacement(): void {
    $this->useValues([
      'layout' => self::$layouts['registerSite'],
      'permission' => CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
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
    Civi\Api4\Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();

    $submission = Civi\Api4\AfformSubmission::get(FALSE)
      ->addOrderBy('id', 'DESC')
      ->execute()->first();

    $this->assertEquals($this->formName, $submission['afform_name']);
    $this->assertIsInt($submission['data']['Activity1'][0]['id']);
    $this->assertIsInt($submission['data']['Individual1'][0]['id']);

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
    // Check that the data overrides form submsision
    $this->assertEquals('Register A site', $contact['source']);
    // Check that the contact and the activity were correctly linked up as per the form.
    $this->callAPISuccessGetSingle('ActivityContact', ['contact_id' => $contact['id'], 'activity_id' => $activity['id']]);
  }

  public function testAboutMeForbidden(): void {
    $this->useValues([
      'layout' => self::$layouts['aboutMe'],
      'permission' => CRM_Core_Permission::ALWAYS_DENY_PERMISSION,
    ]);

    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionTemp = new CRM_Core_Permission_Temp();

    try {
      Civi\Api4\Afform::prefill()
        ->setName($this->formName)
        ->setArgs([])
        ->execute()
        ->indexBy('name');
      $this->fail('Expected authorization exception from Afform.prefill');
    }
    catch (\API_Exception $e) {
      // Should fail permission check
    }

    try {
      Civi\Api4\Afform::submit()
        ->setName($this->formName)
        ->setArgs([])
        ->setValues([
          'does.n' => 'tmatter',
        ])
        ->execute();
      $this->fail('Expected authorization exception from Afform.submit');
    }
    catch (\API_Exception $e) {
      // Should fail permission check
    }
  }

  public function testEmployerReference(): void {
    $this->useValues([
      'layout' => self::$layouts['employer'],
      'permission' => CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
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
    Civi\Api4\Afform::submit()
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
      'permission' => CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
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
    Civi\Api4\Afform::submit()
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
      'permission' => CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $individualEmail = uniqid('individual@') . '.test';
    $orgEmail = uniqid('org@') . '.test';
    $locationType = CRM_Core_BAO_LocationType::getDefault()->id;
    $values = [
      'Individual1' => [
        [
          'fields' => [
            'employer_id' => 'Organization1',
          ],
          'joins' => [
            'Email' => [
              ['email' => $individualEmail, 'location_type_id' => $locationType],
            ],
          ],
        ],
      ],
      'Organization1' => [
        [
          'fields' => [],
          'joins' => [
            'Email' => [
              ['email' => $orgEmail, 'location_type_id' => $locationType],
            ],
          ],
        ],
      ],
    ];
    Civi\Api4\Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();
    $contact = \Civi\Api4\Contact::get()
      ->addWhere('display_name', '=', $individualEmail)
      ->addJoin('Contact AS org', 'LEFT', ['employer_id', '=', 'org.id'])
      ->addSelect('display_name', 'org.display_name')
      ->execute()->first();
    $this->assertEquals($orgEmail, $contact['org.display_name']);
  }

}
