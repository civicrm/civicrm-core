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
    return \Civi\Test::headless()->installMe(__DIR__)->install('org.civicrm.search_kit')->apply();
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
    // Check modified date is reasonable
    $this->assertGreaterThan('2023-01-01 12:00:00', $result['modified_date']);
    // Hopefully this test won't need updating for the next 2000 years or so...
    $this->assertLessThan('4000-01-01 12:00:00', $result['modified_date']);

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
    $this->assertArrayNotHasKey('base_module', $result);
  }

  public function testGetLayoutWithEmptyNode() {
    Afform::create(FALSE)
      ->addValue('name', $this->formName)
      ->addValue('title', 'Test Form')
      ->addValue('layout', '<af-form><af-entity name="a"></af-entity><div></div></af-form>')
      ->execute();

    $layout = Afform::get(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute()->single()['layout'];

    // Ensure container elements like <div> always have #children even if empty
    $this->assertEquals([], $layout[0]['#children'][1]['#children']);
    $this->assertArrayNotHasKey('#children', $layout[0]['#children'][0]);
  }

  public function testGetHtmlEncoding(): void {
    Afform::create(FALSE)
      ->setLayoutFormat('shallow')
      ->addValue('name', $this->formName)
      ->addValue('title', 'Test Form')
      ->addValue('layout', [
        [
          '#tag' => 'af-form',
          'ctrl' => 'afform',
          '#children' => [
            [
              '#tag' => 'af-entity',
              'data' => "{source: 'This isn\\'t \"quotes\"'}",
              'type' => 'Individual',
              'name' => 'Individual1',
            ],
          ],
        ],
      ])
      ->execute();

    $html = Afform::get(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->setLayoutFormat('html')
      ->execute()->single()['layout'];

    $expected = <<<HTML
data="{source: 'This isn\'t &quot;quotes&quot;'}"
HTML;

    $this->assertStringContainsString($expected, $html);
  }

  public function testAfformAutocomplete(): void {
    // Use a numeric title to test that the "search by id" feature
    // doesn't kick in for Afforms (which don't have a numeric "id")
    $title = (string) rand(1000, 999999);
    Afform::create()
      ->addValue('name', $this->formName)
      ->addValue('title', $title)
      ->addValue('type', 'form')
      ->execute();

    $result = Afform::autocomplete()
      ->setInput(substr($title, 0, 9))
      ->execute();

    $this->assertEquals($this->formName, $result[0]['id']);
    $this->assertEquals($title, $result[0]['label']);
    $this->assertEquals('fa-list-alt', $result[0]['icon']);
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

  public function testGetLayoutWithLegacyContactTypeConversion() {
    Afform::create(FALSE)
      ->addValue('name', $this->formName)
      ->addValue('title', 'Test Form')
      ->addValue('layout', <<<'AFFORM'
        <af-form>
          <af-entity type="Contact" data="{'contact_type': 'Organization' num: 1}"></af-entity>
          <af-entity data="{thing: 2 contact_type: 'Household'}" type="Contact"></af-entity>
          <af-entity data="{contact_type: 'Individual'}" type='Contact'></af-entity>
          <fieldset>Hello</fieldset>
        </af-form>
        AFFORM
      )->execute();

    $entity = Afform::get(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->setFormatWhitespace(TRUE)
      ->execute()->single()['layout'][0]['#children'];

    // Legacy contact_type should have been converted to entity type
    $this->assertEquals('Organization', $entity[0]['type']);
    $this->assertCount(1, $entity[0]['data']);
    $this->assertEquals(1, $entity[0]['data']['num']);
    $this->assertEquals('Household', $entity[1]['type']);
    $this->assertCount(1, $entity[1]['data']);
    $this->assertEquals(2, $entity[1]['data']['thing']);
    $this->assertEquals('Individual', $entity[2]['type']);
    $this->assertCount(0, $entity[2]['data']);
  }

}
