<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;
use Civi\Test\TransactionalInterface;

/**
 * Test case for Afform with autocomplete.
 *
 * @group headless
 */
class AfformConditionalUsageTest extends AfformUsageTestCase implements TransactionalInterface {

  /**
   * Required field based on text input
   */
  public function testConditionalRequiredFieldTextInput(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{source: 'TestConditionals'}" type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <af-field name="first_name" />
    <af-field name="last_name" af-if="([[&amp;quot;OR&amp;quot;,[[&amp;quot;Individual1[0][fields][first_name]&amp;quot;,&amp;quot;=&amp;quot;,&amp;quot;\&amp;quot;A\&amp;quot;&amp;quot;],[&amp;quot;Individual1[0][fields][first_name]&amp;quot;,&amp;quot;CONTAINS&amp;quot;,&amp;quot;\&amp;quot;BC\&amp;quot;&amp;quot;]]]])" defn="{required: true, input_attrs: {}}" />
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()" ng-if="afform.showSubmitButton">Submit</button>
</af-form>
EOHTML;
    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Conditional rule is that last_name is required
    // IF first_name = a (case-insensitive) OR first_name CONTAINS "bc" (case-insensitive)

    // Conditional field shown: this will fail validation
    $submission = [
      ['fields' => ['first_name' => 'a', 'last_name' => '']],
    ];
    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues(['Individual1' => $submission])
        ->execute();
    }
    catch (\CRM_Core_Exception $e) {
    }
    $this->assertStringContainsString('Last Name is a required field.', $e->getMessage());

    // Conditional field shown: this will fail validation
    $submission = [
      ['fields' => ['first_name' => 'ebcd', 'last_name' => '']],
    ];
    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues(['Individual1' => $submission])
        ->execute();
    }
    catch (\CRM_Core_Exception $e) {
    }
    $this->assertStringContainsString('Last Name is a required field.', $e->getMessage());

    // Conditional field hidden: this will pass validation
    $submission = [
      ['fields' => ['first_name' => 'q', 'last_name' => '']],
    ];
    $result = Afform::submit()
      ->setName($this->formName)
      ->setValues(['Individual1' => $submission])
      ->execute();

    $this->assertGreaterThan(0, $result[0]['Individual1'][0]['id']);
  }

  /**
   * Required field based on boolean input
   */
  public function testConditionalRequiredFieldBoolInput(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{source: 'TestConditionals'}" type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <af-field name="first_name" />
    <af-field name="last_name" />
    <af-field name="do_not_email" />
    <div af-join="Email" data="{is_primary: true}" af-if="([[&amp;quot;Individual1[0][fields][do_not_email]&amp;quot;,&amp;quot;=&amp;quot;,&amp;quot;false&amp;quot;]])">
      <div class="af-container af-layout-inline">
        <af-field name="email" defn="{required: true, input_attrs: {}}" />
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

    // Conditional rule is that email is required if no_not_email is FALSE

    // Conditional field shown: this will fail validation
    $submission = [
      [
        'fields' => ['first_name' => 'A', 'last_name' => 'A', 'do_not_email' => FALSE],
        'joins' => [
          'Email' => [
            ['email' => ''],
          ],
        ],
      ],
    ];
    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues(['Individual1' => $submission])
        ->execute();
    }
    catch (\CRM_Core_Exception $e) {
    }
    $this->assertStringContainsString('Email is a required field.', $e->getMessage());

    // Conditional field hidden: this will pass validation
    $submission = [
      [
        'fields' => ['first_name' => 'A', 'last_name' => 'A', 'do_not_email' => TRUE],
        'joins' => [
          'Email' => [
            ['email' => ''],
          ],
        ],
      ],
    ];
    $result = Afform::submit()
      ->setName($this->formName)
      ->setValues(['Individual1' => $submission])
      ->execute();

    $this->assertGreaterThan(0, $result[0]['Individual1'][0]['id']);
  }

}
