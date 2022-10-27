<?php
namespace Civi\Afform;

use Civi\Api4\AfformBehavior;
use Civi\Test\HeadlessInterface;

/**
 * @group headless
 */
class AfformBehaviorTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()->installMe(__DIR__)->apply();
  }

  public function testGet() {
    $autofill = AfformBehavior::get(FALSE)
      ->addWhere('key', '=', 'autofill')
      ->execute()->single();

    $this->assertContains('user', array_column($autofill['modes']['Individual'], 'name'));
  }

}
