<?php
namespace Civi\ext\afform\mock\tests\phpunit\api\v4\Afform;

use api\v4\Afform\AfformUsageTestCase;
use Civi\Api4\Afform;

/**
 * Test case for Afform with validation.
 *
 * @group headless
 */
class AfformValidateUsageTest extends AfformUsageTestCase {

  public function testSubmitWithRequiredOnlyFields(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" url-autofill="1" security="RBAC"  />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <af-field name="first_name" defn="{required: true}" />
    <af-field name="last_name" defn="{required: true}" />
    <af-field name="middle_name" />
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Submit with empty first and last names. Should get 2 validation errors.
    $submission = [
      ['fields' => ['middle_name' => 'Person']],
    ];
    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues(['Individual1' => $submission])
        ->execute();
      $this->fail('Should have thrown exception');
    }
    catch (\CRM_Core_Exception $e) {
      $msg = $e->getMessage();
      $this->assertStringContainsString('First Name is a required field', $msg);
      $this->assertStringContainsString('Last Name is a required field', $msg);
    }
  }

  public function testSubmitWithMaxLengthValidation(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" url-autofill="1" security="RBAC"  />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <af-field name="first_name" defn="{input_attrs: {maxlength: 5}}" />
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Submit with first name exceeding maxlength. Should get validation error.
    $submission = [
      ['fields' => ['first_name' => 'TooLongName']],
    ];
    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues(['Individual1' => $submission])
        ->execute();
      $this->fail('Should have thrown exception');
    }
    catch (\CRM_Core_Exception $e) {
      $msg = $e->getMessage();
      $this->assertStringContainsString('First Name', $msg);
      $this->assertStringContainsString('length of 5', $msg);
    }

    Afform::submit()
      ->setName($this->formName)
      ->setValues(['Individual1' => [['fields' => ['first_name' => 'Short']]]])
      ->execute();
  }

  public function testSubmitWithMinMaxValidation(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true}" security="RBAC"  />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <af-field name="external_identifier" defn="{input_type: 'Number', input_attrs: {min: 10, max: 100}}" />
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Submit with value below minimum. Should get validation error.
    $submission = [
      ['fields' => ['external_identifier' => 5]],
    ];
    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues(['Individual1' => $submission])
        ->execute();
      $this->fail('Should have thrown exception');
    }
    catch (\CRM_Core_Exception $e) {
      $msg = $e->getMessage();
      $this->assertStringContainsString('External', $msg);
      $this->assertStringContainsString('to 10', $msg);
    }

    // Submit with value above maximum. Should get validation error.
    $submission = [
      ['fields' => ['external_identifier' => 150]],
    ];
    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues(['Individual1' => $submission])
        ->execute();
      $this->fail('Should have thrown exception');
    }
    catch (\CRM_Core_Exception $e) {
      $msg = $e->getMessage();
      $this->assertStringContainsString('External', $msg);
      $this->assertStringContainsString('to 100', $msg);
    }

    // Submit with valid value. Should succeed.
    Afform::submit()
      ->setName($this->formName)
      ->setValues(['Individual1' => [['fields' => ['external_identifier' => 50]]]])
      ->execute();
  }

  public function testSubmitWithMultiselectMinMaxValidation(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Participant" name="Participant1" label="Participant 1" actions="{create: true}" security="RBAC"  />
  <fieldset af-fieldset="Participant1" class="af-container" af-title="Participant 1">
    <af-field name="role_id" defn="{input_attrs: {min: 2, max: 3}}" />
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Submit with fewer selections than minimum. Should get validation error.
    $submission = [
      ['fields' => ['role_id' => [1]]],
    ];
    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues(['Participant1' => $submission])
        ->execute();
      $this->fail('Should have thrown exception');
    }
    catch (\CRM_Core_Exception $e) {
      $msg = $e->getMessage();
      $this->assertStringContainsString('Participant Role', $msg);
      $this->assertStringContainsString('at least 2', $msg);
    }

    // Submit with more selections than maximum. Should get validation error.
    $submission = [
      ['fields' => ['role_id' => [1, 2, 3, 4]]],
    ];
    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues(['Participant1' => $submission])
        ->execute();
      $this->fail('Should have thrown exception');
    }
    catch (\CRM_Core_Exception $e) {
      $msg = $e->getMessage();
      $this->assertStringContainsString('Participant Role', $msg);
      $this->assertStringContainsString('at most 3', $msg);
    }

    // Submit with valid number of selections. Should succeed.
    Afform::submit()
      ->setName($this->formName)
      ->setValues(['Participant1' => [['fields' => ['role_id' => [1, 2]]]]])
      ->execute();
  }

}
