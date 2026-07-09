<?php

use Civi\Afform\Event\AfformEntitySortEvent;
use Civi\Afform\FormDataModel;
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
      ->apply();
  }

  public static function getNameExamples() {
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

  public static function formEntityWeightExamples() {
    $exs = [];
    $exs[] = [
      '<af-form><div>
         <af-entity type="Contact" name="Individual1"/>
         <af-entity name="Activity1" type="Activity" />
         <fieldset af-fieldset="Individual1" class="af-container">
           <af-field name="first_name" /><af-field name="last_name" />
         </fieldset>
         <fieldset af-fieldset="Activity1" class="af-container">
           <af-field name="source_contact_id" />
         </fieldset>
      </div></af-form>',
      [
        'Individual1' => [['fields' => ['first_name' => 'Test', 'last_name' => 'Contact']]],
        'Activity1' => [['fields' => ['source_contact_id' => 'Individual1']]],
      ],
      [
        'Individual1',
        'Activity1',
      ],
    ];
    $exs[] = [
      '<af-form><div>
         <af-entity type="Contact" name="Individual1"/>
         <af-entity type="Event" name="Event1" />
         <af-entity type="LocBlock" name="LocBlock1" />
         <fieldset af-fieldset="Individual1" class="af-container">
           <af-field name="first_name" /><af-field name="last_name" />
         </fieldset>
         <fieldset af-fieldset="Event1" class="af-container">
           <af-field name="created_id" />
         </fieldset>
         <fieldset af-fieldset="LocBlock1" class="af-container">
           <af-field name="event_id" />
         </fieldset>
      </div></af-form>',
      [
        'Individual1' => [['fields' => ['first_name' => 'Test', 'last_name' => 'Contact']]],
        'Event1' => [['fields' => ['created_id' => 'Individual1']]],
        'LocBlock1' => [['fields' => ['event_id' => 'Event1']]],
      ],
      [
        'Individual1',
        'Event1',
        'LocBlock1',
      ],
    ];
    $exs[] = [
      '<af-form><div>
         <af-entity type="Contact" name="Individual1"/>
         <af-entity type="LocBlock" name="LocBlock1" />
         <af-entity type="Event" name="Event1" />
         <fieldset af-fieldset="Individual1" class="af-container">
           <af-field name="first_name" /><af-field name="last_name" />
         </fieldset>
         <fieldset af-fieldset="LocBlock1" class="af-container">
           <af-field name="event_id" />
         </fieldset>
         <fieldset af-fieldset="Event1" class="af-container">
           <af-field name="created_id" />
         </fieldset>
      </div></af-form>',
      [
        'Individual1' => [['fields' => ['first_name' => 'Test', 'last_name' => 'Contact']]],
        'LocBlock1' => [['fields' => ['event_id' => 'Event1']]],
        'Event1' => [['fields' => ['created_id' => 'Individual1']]],
      ],
      [
        'Individual1',
        'Event1',
        'LocBlock1',
      ],
    ];
    $exs[] = [
      '<af-form><div>
         <af-entity type="Activity" name="Activity1"/>
         <af-entity type="Contact" name="Individual1"/>
         <af-entity type="Contact" name="Individual2"/>
         <af-entity type="Contact" name="Organization1"/>
         <fieldset af-fieldset="Activity1" class="af-container">
           <af-field name="source_contact_id" />
         </fieldset>
         <fieldset af-fieldset="Individual1" class="af-container">
           <af-field name="first_name" /><af-field name="last_name" /><af-field name="employer_id" />
         </fieldset>
         <fieldset af-fieldset="Individual2" class="af-container">
           <af-field name="first_name" /><af-field name="last_name" /><af-field name="employer_id" />
         </fieldset>
         <fieldset af-fieldset="Organization1" class="af-container">
           <af-field name="organization_name" />
         </fieldset>
      </div></af-form>',
      [
        'Activity1' => [['fields' => ['source_contact_id' => 'Individual1', 'target_contact_id' => ['Individual2']]]],
        'Individual1' => [['fields' => ['first_name' => 'Test', 'last_name' => 'Contact', 'employer_id' => 'Organization1']]],
        'Individual2' => [
          ['fields' => ['first_name' => 'Test2', 'last_name' => 'Contact']],
          ['fields' => ['first_name' => 'Test3', 'last_name' => 'Contact', 'employer_id' => 'Organization1']],
        ],
        'Organization1' => [['fields' => ['first_name' => 'Test', 'last_name' => 'Contact']]],
      ],
      [
        'Organization1',
        'Individual1',
        'Individual2',
        'Activity1',
      ],
    ];
    return $exs;
  }

  /**
   * @dataProvider formEntityWeightExamples
   */
  public function testEntityWeights($html, $entityValues, $expectedWeights) {
    $parser = new \CRM_Afform_ArrayHtml();
    $formDataModel = new FormDataModel($parser->convertHtmlToArray($html));
    $sorter = new AfformEntitySortEvent([], $formDataModel, new \Civi\Api4\Generic\BasicGetAction('', ''), $entityValues);
    \Civi::dispatcher()->dispatch('civi.afform.sort.submit', $sorter);
    $entityWeights = $sorter->getSorted();

    $this->assertEquals($expectedWeights, $entityWeights);
  }

}
