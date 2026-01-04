<?php
namespace Civi\ext\afform\mock\tests\phpunit\api\v4\Afform;

use api\v4\Afform\AfformUsageTestCase;
use Civi\Api4\Afform;

/**
 * Test case for Afform with autocomplete.
 *
 * @group headless
 */
class AfformSubmitUsageTest extends AfformUsageTestCase {

  public function testSubmitWithDisplayOnlyFields(): void {

    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" url-autofill="1" security="RBAC"  />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <af-field name="first_name" defn="{input_type: 'DisplayOnly', required: true}" />
    <af-field name="last_name" />
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $cid = $this->saveTestRecords('Individual', [
      'records' => [
        ['first_name' => 'One', 'last_name' => 'Person'],
      ],
    ])->column('id');

    $prefill = Afform::prefill()
      ->setName($this->formName)
      ->setFillMode('form')
      ->setArgs(['Individual1' => $cid])
      ->execute()
      ->indexBy('name');
    $this->assertCount(1, $prefill['Individual1']['values']);
    $this->assertEquals('One', $prefill['Individual1']['values'][0]['fields']['first_name']);
    $this->assertEquals('Person', $prefill['Individual1']['values'][0]['fields']['last_name']);

    // Submit with empty first_name: should not hit a validation error because DisplayOnly fields cannot be required
    $submission = [
      ['fields' => ['last_name' => 'Person']],
    ];
    $result = Afform::submit()
      ->setName($this->formName)
      ->setValues(['Individual1' => $submission])
      ->setArgs(['Individual1' => $cid])
      ->execute();
    $this->assertSame($cid[0], $result[0]['Individual1'][0]['id']);
  }

}
