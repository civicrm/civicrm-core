<?php
namespace Civi\Afform;

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class FormDataModelTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless() {
    return \Civi\Test::headless()->installMe(__DIR__)->apply();
  }

  public function getEntityExamples() {
    $cases = [];

    //$cases[] = [
    //  'html' => 'Hello world',
    //  'entities' => [],
    //];
    //
    //$cases[] = [
    //  'html' => '<div/>',
    //  'entities' => [],
    //];
    //
    //$cases[] = [
    //  'html' => '<div>Hello world</div>',
    //  'entities' => [],
    //];

    $cases[] = [
      'html' => '<af-form><af-entity type="Foo" name="foobar"/><af-fieldset model="foobar"><af-field name="propA" /><af-field name="propB" defn="{title: \'Whiz\'}" /></af-fieldset></af-form>',
      'entities' => [
        'foobar' => [
          'type' => 'Foo',
          'name' => 'foobar',
          'fields' => [
            ['name' => 'propA'],
            ['name' => 'propB', 'defn' => ['title' => 'Whiz']],
          ],
        ],
      ],
    ];

    return $cases;
  }

  /**
   * @param $html
   * @param $expectEntities
   * @dataProvider getEntityExamples
   */
  public function testGetEntities($html, $expectEntities) {
    $parser = new \CRM_Afform_ArrayHtml();
    $fdm = FormDataModel::create($parser->convertHtmlToArray($html));
    $this->assertEquals($expectEntities, $fdm->getEntities());
  }

}
