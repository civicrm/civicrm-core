<?php

/**
 * Test case for Afform.prefill and Afform.submit.
 *
 * @group headless
 */
abstract class api_v4_AfformUsageTestCase extends api_v4_AfformTestCase {
  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\ContactTestTrait;

  protected static $layouts = [];

  protected $formName;

  public function setUp(): void {
    parent::setUp();
    Civi\Api4\Afform::revert(FALSE)
      ->addWhere('type', '=', 'block')
      ->execute();
    $this->formName = 'mock' . rand(0, 100000);
  }

  public function tearDown(): void {
    Civi\Api4\Afform::revert(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute();
    parent::tearDown();
  }

  protected function useValues($values) {
    $defaults = [
      'title' => 'My form',
      'name' => $this->formName,
    ];
    $full = array_merge($defaults, $values);
    Civi\Api4\Afform::create(FALSE)
      ->setLayoutFormat('html')
      ->setValues($full)
      ->execute();
  }

}
