<?php
namespace Civi\ext\afform\mock\tests\phpunit\api\v4\Afform;

use api\v4\Afform\AfformUsageTestCase;
use Civi\Api4\Afform;

/**
 * Test case for Afform with API4 style suffixes.
 *
 * @group headless
 */
class AfformSuffixSubmitUsageTest extends AfformUsageTestCase {

  public function testSubmitWithPrefixIdNameSuffix(): void {

    // Ensure the entity we are testing with exists
    $prefixValue = \Civi\Api4\OptionValue::save(FALSE)
      ->addRecord([
        'name' => 'Mr.',
        'option_group_id:name' => 'individual_prefix',
      ])
      ->setMatch([
        'option_group_id',
        'name',
      ])
      ->setReload([
        'value',
      ])
      ->execute()
      ->first()['value'];

    // Specify prefix_id with :name suffix
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{source: 'prefix', 'prefix_id:name': 'Mr.'}" type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <af-field name="first_name" />
    <af-field name="last_name" />
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()" ng-if="afform.showSubmitButton">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Submit without the default value (prefix_id:name) and then check it was correctly written on submit
    $submission = [
      [
        'fields' => [
          'first_name' => 'Joe',
          'last_name' => 'Bloggs',
        ],
      ],
    ];
    $result = Afform::submit(FALSE)
      ->setName($this->formName)
      ->setValues(['Individual1' => $submission])
      ->execute();

    $resultId = $result[0]['Individual1'][0]['id'];
    $resultPrefix = \Civi\Api4\Individual::get(FALSE)
      ->addSelect('prefix_id')
      ->addWhere('id', '=', $resultId)
      ->execute()
      ->first()['prefix_id'];

    $this->assertSame((string) $prefixValue, (string) $resultPrefix);
  }

}
