<?php

/**
 * Test case for Afform.prefill and Afform.submit.
 *
 * @group headless
 */
class api_v4_AfformUsageTest extends api_v4_AfformTestCase {
  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\ContactTestTrait;

  protected static $layouts = [];

  protected $formName;

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    self::$layouts['aboutMe'] = <<<EOHTML
<af-form ctrl="modelListCtrl">
  <af-entity type="Contact" data="{contact_type: 'Individual'}" name="me" label="Myself" url-autofill="1" autofill="user" />
  <fieldset af-fieldset="me">
      <af-field name="first_name" />
      <af-field name="last_name" />
  </fieldset>
</af-form>
EOHTML;
    self::$layouts['registerSite'] = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual', source: 'Register A site'}" url-autofill="1" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="FBAC" />
  <af-entity url-autofill="1" type="Activity" name="Activity1" label="Activity 1" data="{activity_type_id: '1', source_contact_id: 'Individual1'}" actions="{create: true, update: true}" security="FBAC" />
  <fieldset af-fieldset="Individual1">
      <af-field name="first_name" />
      <af-field name="last_name" />
  </fieldset>
  <fieldset af-fieldset="Activity1">
    <legend class="af-text">Activity 1</legend>
    <af-field name="subject" />
  </fieldset>
</af-form>
EOHTML;
  }

  public function setUp(): void {
    parent::setUp();
    $this->formName = 'mock' . rand(0, 100000);
  }

  public function tearDown(): void {
    Civi\Api4\Afform::revert()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute();
    parent::tearDown();
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
      ->setArgs([])
      ->execute()
      ->indexBy('name');
    $this->assertEquals('Logged In', $prefill['me']['values'][0]['fields']['first_name']);
    $this->assertRegExp('/^User/', $prefill['me']['values'][0]['fields']['last_name']);

    $me = $prefill['me']['values'];
    $me[0]['fields']['first_name'] = 'Firsty';
    $me[0]['fields']['last_name'] = 'Lasty';

    Civi\Api4\Afform::submit()
      ->setName($this->formName)
      ->setArgs([])
      ->setValues(['me' => $me])
      ->execute();

    $contact = Civi\Api4\Contact::get()->setCheckPermissions(FALSE)->addWhere('id', '=', $cid)->execute()->first();
    $this->assertEquals('Firsty', $contact['first_name']);
    $this->assertEquals('Lasty', $contact['last_name']);
  }

  public function testRegisterSite(): void {
    $this->useValues([
      'layout' => self::$layouts['registerSite'],
      'permission' => CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    CRM_Core_Config::singleton()->userPermissionTemp = new CRM_Core_Permission_Temp();

    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => 'Test Register',
            'last_name' => 'site',
            'source' => 'test source',
          ],
        ],
      ],
      'Activity1' => [
        [
          'fields' => [
            'subject' => 'Test Register Site Form Submission',
          ],
        ],
      ],
    ];
    Civi\Api4\Afform::submit()
      ->setName($this->formName)
      ->setArgs([])
      ->setValues($values)
      ->execute();
    // Check that Activity was submitted correctly.
    $activity = \Civi\Api4\Activity::get()->setCheckPermissions(FALSE)->execute()->first();
    $this->assertEquals('Test Register Site Form Submission', $activity['subject']);
    $contact = \Civi\Api4\Contact::get()->addWhere('first_name', '=', 'Test Register')->execute()->first();
    $this->assertEquals('site', $contact['last_name']);
    // Check that the data overrides form submsision
    $this->assertEquals('Register A site', $contact['source']);
    // Check that the contact and the activity were correctly linked up as per the form.
    $this->callAPISuccess('ActivityContact', 'get', ['contact_id' => $contact['id'], 'activity_id' => $activity['id']]);

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

  protected function useValues($values) {
    $defaults = [
      'title' => 'My form',
      'name' => $this->formName,
    ];
    $full = array_merge($defaults, $values);
    Civi\Api4\Afform::create()
      ->setCheckPermissions(FALSE)
      ->setLayoutFormat('html')
      ->setValues($full)
      ->execute();
  }

}
