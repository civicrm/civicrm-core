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

  public static function getEntityExamples() {
    $cases = [];

    $cases[] = [
      'html' => 'Hello world',
      'entities' => [],
    ];

    $cases[] = [
      'html' => '<div/>',
      'entities' => [],
    ];

    $cases[] = [
      'html' => '<div>Hello world</div>',
      'entities' => [],
    ];

    $cases[] = [
      'html' => '<af-form><af-entity type="Foo" name="foobar"/><div af-fieldset="foobar" af-repeat><af-field name="propA" /><span><p><af-field name="propB" defn="{title: \'Whiz\'}" /></p></span></div></af-form>',
      'entities' => [
        'foobar' => [
          'type' => 'Foo',
          'name' => 'foobar',
          'fields' => [
            'propA' => ['name' => 'propA'],
            'propB' => ['name' => 'propB', 'defn' => ['title' => 'Whiz']],
          ],
          'joins' => [],
          'security' => 'RBAC',
          'actions' => ['create' => 1, 'update' => 1],
          'min' => 0,
          'max' => NULL,
        ],
      ],
    ];

    $cases[] = [
      'html' => '<af-form><div><af-entity type="Foo" name="foobar"/><af-entity name="whiz_bang" type="Whiz" /></div></af-form>',
      'entities' => [
        'foobar' => [
          'type' => 'Foo',
          'name' => 'foobar',
          'fields' => [],
          'joins' => [],
          'security' => 'RBAC',
          'actions' => ['create' => 1, 'update' => 1],
          'min' => 1,
          'max' => 1,
        ],
        'whiz_bang' => [
          'type' => 'Whiz',
          'name' => 'whiz_bang',
          'fields' => [],
          'joins' => [],
          'security' => 'RBAC',
          'actions' => ['create' => 1, 'update' => 1],
          'min' => 1,
          'max' => 1,
        ],
      ],
    ];

    $cases[] = [
      'html' => '<af-form><div><af-entity type="Foo" name="foobar" security="FBAC" actions="{create: false, update: true}"/><div af-fieldset="foobar" af-repeat min="1" max="2"></div></div></af-form>',
      'entities' => [
        'foobar' => [
          'type' => 'Foo',
          'name' => 'foobar',
          'fields' => [],
          'joins' => [],
          'security' => 'FBAC',
          'actions' => ['create' => FALSE, 'update' => TRUE],
          'min' => 1,
          'max' => 2,
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
  public function testGetEntities($html, $expectEntities): void {
    $parser = new \CRM_Afform_ArrayHtml();
    $fdm = new FormDataModel($parser->convertHtmlToArray($html));
    $this->assertEquals($expectEntities, $fdm->getEntities());
  }

}
