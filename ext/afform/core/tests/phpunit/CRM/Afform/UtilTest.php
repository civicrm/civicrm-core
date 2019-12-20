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
  public function testNameConversion($inputFileName, $toFormat, $expected) {
    $actual = _afform_angular_module_name($inputFileName, $toFormat);
    $this->assertEquals($expected, $actual);
  }

}
