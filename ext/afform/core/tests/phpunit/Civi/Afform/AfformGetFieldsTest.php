<?php
namespace Civi\Afform;

use Civi\Api4\Afform;
use Civi\Test\HeadlessInterface;

/**
 * @group headless
 */
class AfformGetFieldsTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()->installMe(__DIR__)->apply();
  }

  public function testGetFields() {
    $fields = Afform::getFields(FALSE)
      ->setAction('get')
      ->execute()->indexBy('name');
    $this->assertTrue($fields['type']['options']);
    $this->assertEquals(['name', 'label', 'icon', 'description'], $fields['type']['suffixes']);

    $this->assertTrue($fields['base_module']['options']);
    $this->assertTrue($fields['contact_summary']['options']);
  }

}
