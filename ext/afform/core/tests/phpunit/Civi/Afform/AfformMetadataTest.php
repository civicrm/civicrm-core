<?php
namespace Civi\Afform;

use Civi\Api4\Afform;
use Civi\Test\HeadlessInterface;

/**
 * @group headless
 */
class AfformMetadataTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()->install(['org.civicrm.search_kit', 'org.civicrm.afform', 'org.civicrm.afform_admin'])->apply();
  }

  public function testGetFields():void {
    $fields = Afform::getFields(FALSE)
      ->setAction('get')
      ->execute()->indexBy('name');
    $this->assertTrue($fields['type']['options']);
    $this->assertEquals(['name', 'label', 'icon', 'description'], $fields['type']['suffixes']);

    $this->assertTrue($fields['base_module']['options']);
    $this->assertTrue($fields['placement']['options']);
  }

  public function testGetEntityFields():void {
    $individualFields = \Civi\AfformAdmin\AfformAdminMeta::getFields('Individual');

    // Ensure the "Existing" contact field exists
    $this->assertEquals('Existing Contact', $individualFields['id']['label']);
    $this->assertEquals('EntityRef', $individualFields['id']['input_type']);
  }

}
