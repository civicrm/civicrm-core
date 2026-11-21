<?php

namespace Civi\Shimmy\Mixins;

/**
 * Assert that the managed-entity mixin is working properly.
 *
 * This class defines the assertions to run when installing or uninstalling the extension.
 * It use called as part of E2E_Shimmy_LifecycleTest.
 *
 * @see E2E_Shimmy_LifecycleTest
 */
class ElementsV1Test extends \PHPUnit\Framework\Assert {

  public function testPreConditions($cv): void {
    $this->assertFileExists(static::getPath('/element/shimmy-tag-a.js'), 'The shimmy extension must have example file shimmy-tag-a.js.');
    $this->assertFileExists(static::getPath('/element/shimmy-tag-a.css'), 'The shimmy extension must have example file shimmy-tag-a.css.');
    $this->assertFileExists(static::getPath('/element/shimmy-tag-b.mjs'), 'The shimmy extension must have example file shimmy-tag-b.mjs.');
  }

  private function getAllElements($cv): array {
    return $cv->phpEval('
      $svc = "elements@1";
      $c = \Civi::container();
      return $c->has($svc) ? $c->get($svc)->getAll() : [];
    ');
  }

  public function testInstalled($cv): void {
    $items = $this->trimFileNames($this->getAllElements($cv));
    $this->assertEquals(['shimmy/element/shimmy-tag-a.js'], $items['shimmy-tag-a']['js'] ?? 'MISSING', '<shimmy-tag-a> should have JS file');
    $this->assertEquals(['shimmy/element/shimmy-tag-a.css'], $items['shimmy-tag-a']['css'] ?? 'MISSING', '<shimmy-tag-a> should have CSS file');
    $this->assertEquals(['shimmy/element/shimmy-tag-b.mjs'], $items['shimmy-tag-b']['js'] ?? 'MISSING', '<shimmy-tag-b> should have JS file');
    $this->assertTrue(empty($items['shimmy-tag-b']['css']), 'shimmy-tag-b should not have any CSS');
  }

  protected function trimFileNames($items): array {
    foreach ($items as $elementName => &$element) {
      foreach ($element as $field => &$value) {
        if ($field === 'js' || $field === 'css') {
          $value = array_map(fn($file) => preg_replace(';(\?ts=.*)$;', '', $file), $value);
        }
      }
    }
    return $items;
  }

  public function testDisabled($cv): void {
    $items = $this->getAllElements($cv);
    $this->assertTrue(!isset($items['shimmy-tag-a']), '<shimmy-tag-a> should not be defined.');
    $this->assertTrue(!isset($items['shimmy-tag-b']), '<shimmy-tag-b> should not be defined.');
  }

  public function testUninstalled($cv): void {
    $this->testDisabled($cv);
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

}
