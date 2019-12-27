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

  public static function setUpBeforeClass() {
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
  }

  public function setUp() {
    parent::setUp();
    $this->formName = 'mock' . rand(0, 100000);
  }

  public function tearDown() {
    Civi\Api4\Afform::revert()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute();
    parent::tearDown();
  }

  public function testAboutMeAllowed() {
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

  public function testAboutMeForbidden() {
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
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertRegExp(';Authorization failed: Cannot process form mock\d+;', $e->getMessage());
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
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertRegExp(';Authorization failed: Cannot process form mock\d+;', $e->getMessage());
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
