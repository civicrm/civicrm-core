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
          'af-repeat' => TRUE,
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
          'af-repeat' => FALSE,
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
          'af-repeat' => FALSE,
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
          'af-repeat' => TRUE,
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
    $expectEntities['extra'] = [
      'type' => NULL,
      'fields' => [],
    ];
    $parser = new \CRM_Afform_ArrayHtml();
    $fdm = new FormDataModel($parser->convertHtmlToArray($html));
    $this->assertEquals($expectEntities, $fdm->getEntities());
  }

  public function testParseConditionalExpression(): void {
    $original = ['AND', [
      ['[Individual1.0.first_name]', '=', 'John'],
      ['[Individual1.0.last_name]', '=', 'Coltrane'],
    ]];

    $canonicalExpression = \htmlentities(\json_encode($original));
    $parsed = FormDataModel::parseExpression($canonicalExpression);

    $this->assertSame($original, $parsed, 'Canonical expression parsing returns original');

    // try deriving this with php ;)
    $angularJsExpression = '([[&amp;quot;Individual1[0][fields][first_name]&amp;quot;,&amp;quot;=&amp;quot;,&amp;quot;\&amp;quot;John\&amp;quot;&amp;quot;],[&amp;quot;Individual1[0][fields][last_name]&amp;quot;,&amp;quot;=&amp;quot;,&amp;quot;\&amp;quot;Coltrane\&amp;quot;&amp;quot;]])';

    $parsed = FormDataModel::parseExpression($angularJsExpression, 'AngularJS expression parsing returns original');
    $this->assertSame($original, $parsed);
  }

}
