<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;
use Civi\Api4\CustomGroup;

/**
 * Test case for Afform.prefill and Afform.submit.
 *
 * @group headless
 */
abstract class AfformUsageTestCase extends AfformTestCase {
  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\ContactTestTrait;

  protected static $layouts = [];

  protected $formName;

  public function setUp(): void {
    parent::setUp();
    Afform::revert(FALSE)
      ->addWhere('type', '=', 'block')
      ->execute();
    $this->formName = 'mock' . rand(0, 100000);
  }

  public function tearDown(): void {
    Afform::revert(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute();
    CustomGroup::delete(FALSE)
      ->addWhere('id', '>', 0)
      ->execute();
    parent::tearDown();
  }

  protected function useValues($values) {
    $defaults = [
      'title' => 'My form',
      'name' => $this->formName,
    ];
    $full = array_merge($defaults, $values);
    Afform::create(FALSE)
      ->setLayoutFormat('html')
      ->setValues($full)
      ->execute();
  }

}
