<?php
namespace Civi\Afform;

use Civi\Api4\Afform;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class AfformGetTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  private $formName = 'abc_123_test';

  public function setUpHeadless() {
    return \Civi\Test::headless()->installMe(__DIR__)->apply();
  }

  public function tearDown(): void {
    Afform::revert(FALSE)->addWhere('name', '=', $this->formName)->execute();
    parent::tearDown();
  }

  public function testGetReturnFields() {
    Afform::create()
      ->addValue('name', $this->formName)
      ->addValue('title', 'Test Form')
      ->execute();

    // Omitting select should return regular fields but not extra fields
    $result = Afform::get()
      ->addWhere('name', '=', $this->formName)
      ->execute()->single();
    $this->assertEquals($this->formName, $result['name']);
    $this->assertArrayNotHasKey('directive_name', $result);
    $this->assertArrayNotHasKey('has_base', $result);

    // Select * should also return regular fields only
    $result = Afform::get()
      ->addSelect('*')
      ->addWhere('name', '=', $this->formName)
      ->execute()->single();
    $this->assertEquals($this->formName, $result['name']);
    $this->assertArrayNotHasKey('module_name', $result);
    $this->assertArrayNotHasKey('has_local', $result);

    // Selecting * and has_base should return core and that one extra field
    $result = Afform::get()
      ->addSelect('*', 'has_base')
      ->addWhere('name', '=', $this->formName)
      ->execute()->single();
    $this->assertEquals($this->formName, $result['name']);
    $this->assertFalse($result['has_base']);
    $this->assertArrayNotHasKey('has_local', $result);
  }

  public function testGetSearchDisplays() {
    Afform::create()
      ->addValue('name', $this->formName)
      ->addValue('title', 'Test Form')
      ->addValue('layout', '<div><crm-search-display-grid search-name="foo" display-name="foo-bar" /></div>< crm-search-display-table search-name=\'foo\' display-name = \'bar-food\' >')
      ->setLayoutFormat('html')
      ->execute();

    $result = Afform::get()
      ->addSelect('name', 'search_displays')
      ->addWhere('name', '=', $this->formName)
      ->addWhere('search_displays', 'CONTAINS', 'foo.foo-bar')
      ->execute()->single();

    $this->assertEquals(['foo.foo-bar', 'foo.bar-food'], $result['search_displays']);
  }

}
