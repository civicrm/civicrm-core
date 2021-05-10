<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class CRM_Afform_UtilTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->install(version_compare(CRM_Utils_System::version(), '5.19.alpha1', '<') ? ['org.civicrm.api4'] : [])
      ->apply();
  }

  public function getNameExamples() {
    $exs = [];
    $exs[] = ['ab-cd-ef', 'camel', 'abCdEf'];
    $exs[] = ['abCd', 'camel', 'abCd'];
    $exs[] = ['AbCd', 'camel', 'abCd'];
    $exs[] = ['ab-cd', 'dash', 'ab-cd'];
    $exs[] = ['abCd', 'dash', 'ab-cd'];
    $exs[] = ['AbCd', 'dash', 'ab-cd'];

    $exs[] = ['ab-cd-ef23', 'camel', 'abCdEf23'];
    $exs[] = ['abCd23', 'camel', 'abCd23'];
    $exs[] = ['AbCd23', 'camel', 'abCd23'];
    $exs[] = ['ab-cd23', 'dash', 'ab-cd23'];
    $exs[] = ['abCd23', 'dash', 'ab-cd23'];
    $exs[] = ['AbCd23', 'dash', 'ab-cd23'];

    $exs[] = ['Custom_fooBar', 'camel', 'customFooBar'];
    $exs[] = ['Custom_Foo__Bar', 'camel', 'customFooBar'];
    $exs[] = ['Custom Foo_ _Bar', 'camel', 'customFooBar'];
    $exs[] = ['Custom_fooBar', 'dash', 'custom-foo-bar'];
    $exs[] = ['Custom_Foo__Bar', 'dash', 'custom-foo-bar'];
    $exs[] = ['Custom Foo_ _Bar', 'dash', 'custom-foo-bar'];

    return $exs;
  }

  /**
   * @param $inputFileName
   * @param $toFormat
   * @param $expected
   *
   * @dataProvider getNameExamples
   * @throws \Exception
   */
  public function testNameConversion($inputFileName, $toFormat, $expected): void {
    $actual = _afform_angular_module_name($inputFileName, $toFormat);
    $this->assertEquals($expected, $actual);
  }


  public function formEntityWeightExampls() {
    $exs = [];
    $exs[] = [
      [
        'Individual1' => ['type' => 'Contact', ['fields' => ['first_name', 'last_name']]],
        'Activity1' => ['type' => 'Activity', ['fields' => ['source_contact_id']]],
      ],
      [
        'Contact' => ['Individual1' => ['fields' => ['first_name' => 'Test', 'last_name' => 'Contact']]],
        'Activity' => ['Activity1' => ['fields' => ['source_contact_id' => 'Individual1']]],
      ],
      [
        'Individual1' => 1,
        'Activity1' => 2,
      ],
    ];
    $exs[] = [
      [
        'Individual1' => ['type' => 'Contact', ['fields' => ['first_name', 'last_name']]],
        'Event1' => ['type' => 'Event', ['fields' => ['created_id']]],
        'LocBlock1' => ['type' => 'LocBlock', ['fields' => ['event_id']]],
      ],
      [
        'Contact' => ['Individual1' => ['fields' => ['first_name' => 'Test', 'last_name' => 'Contact']]],
        'Event' => ['Event1' => ['fields' => ['created_id' => 'Individual1']]],
        'LocBlock' => ['LocBlock1' => ['fields' => ['event_id' => 'Event1']]],
      ],
      [
        'Individual1' => 1,
        'Event1' => 2,
        'LocBlock1' => 3,
      ],
    ];
    $exs[] = [
      [
        'Individual1' => ['type' => 'Contact', ['fields' => ['first_name', 'last_name']]],
        'LocBlock1' => ['type' => 'LocBlock', ['fields' => ['event_id']]],
        'Event1' => ['type' => 'Event', ['fields' => ['created_id']]],
      ],
      [
        'Contact' => ['Individual1' => ['fields' => ['first_name' => 'Test', 'last_name' => 'Contact']]],
        'LocBlock' => ['LocBlock1' => ['fields' => ['event_id' => 'Event1']]],
        'Event' => ['Event1' => ['fields' => ['created_id' => 'Individual1']]],
      ],
      [
        'Individual1' => 1,
        'Event1' => 2,
        'LocBlock1' => 3,
      ],
    ];
    return $exs;
  }

  /**
   * @dataProvider formEntityWeightExampls
   */
  public function testEntityWeights($formEntities, $entityValues, $expectedWeights) {
    $this->assertEquals($expectedWeights, CRM_Afform_Utils::getEntityWeights($formEntities, $entityValues));
  }

}
