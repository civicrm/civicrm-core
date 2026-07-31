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
    $this->conditionallyDeleteTestRecords();
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

  /**
   * Asserts the result has a blocking error, and returns its messages combined into one string.
   *
   * @param \Civi\Api4\Generic\Result $result
   * @return string
   */
  protected function getBlockingErrorMessages(\Civi\Api4\Generic\Result $result): string {
    $response = $result->first();
    $this->assertTrue($response['is_blocking_error'] ?? FALSE, 'Expected a blocking validation error');
    return implode("\n", array_map(fn (\Civi\Api4\Generic\Error $error) => $error->getMessage(), $response['errors']));
  }

}
