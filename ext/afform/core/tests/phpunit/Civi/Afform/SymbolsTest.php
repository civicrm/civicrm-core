<?php
namespace Civi\Afform;

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SymbolsTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

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

  public function getExamples() {
    $exs = [];
    $exs[] = [
      '<div/>',
      [
        'e' => ['div' => 1, 'body' => 1],
        'a' => [],
        'c' => [],
      ],
    ];
    $exs[] = [
      '<my-tabset><my-tab id="1"/><my-tab id="2">foo</my-tab><my-tab id="3">bar</my-tab></my-tabset>',
      [
        'e' => ['my-tabset' => 1, 'my-tab' => 3, 'body' => 1],
        'a' => ['id' => 3],
        'c' => [],
      ],
    ];
    $exs[] = [
      '<div class="my-parent"><div class="my-child"><img class="special" src="foo.png"/></div></div>',
      [
        'e' => ['div' => 2, 'img' => 1, 'body' => 1],
        'a' => ['class' => 3, 'src' => 1],
        'c' => [
          'my-parent' => 1,
          'my-child' => 1,
          'special' => 1,
        ],
      ],
    ];
    $exs[] = [
      '<div class="my-parent foo bar">a<div class="my-child whiz bang {{ghost + stuff}} last">b</div>c</div>',
      [
        'e' => ['div' => 2, 'body' => 1],
        'a' => ['class' => 2],
        'c' => [
          'my-parent' => 1,
          'my-child' => 1,
          'foo' => 1,
          'bar' => 1,
          'whiz' => 1,
          'bang' => 1,
          '{{ghost + stuff}}' => 1,
          'last' => 1,
        ],
      ],
    ];
    $exs[] = [
      '<div class="{{make[\'cheese\']}} {{ghost + stuff}} {{a}}_{{b}}"/>',
      [
        'e' => ['div' => 1, 'body' => 1],
        'a' => ['class' => 1],
        'c' => [
          '{{ghost + stuff}}' => 1,
          '{{make[\'cheese\']}}' => 1,
          '{{a}}_{{b}}' => 1,
        ],
      ],
    ];

    return $exs;
  }

  /**
   * @param string $html
   * @param array $expect
   *   List of expected symbol counts, by type.
   *   Types are (e)lement, (a)ttribute, (c)lass
   * @dataProvider getExamples
   */
  public function testSymbols($html, $expect): void {
    $expectDefaults = ['e' => [], 'a' => [], 'c' => []];
    $expect = array_merge($expectDefaults, $expect);
    $actual = Symbols::scan($html);

    $this->assertEquals($expect['e'], $actual->elements);
    $this->assertEquals($expect['a'], $actual->attributes);
    $this->assertEquals($expect['c'], $actual->classes);
  }

}
