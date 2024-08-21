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

  public function testGetIndividualFields():void {
    $individualFields = \Civi\AfformAdmin\AfformAdminMeta::getFields('Individual');

    // Ensure the "Existing Contact" `id` field exists
    $this->assertEquals('Existing Individual', $individualFields['id']['label']);
    $this->assertEquals('EntityRef', $individualFields['id']['input_type']);
  }

  public function testGetLocBlockFields():void {
    $fields = \Civi\AfformAdmin\AfformAdminMeta::getFields('LocBlock');

    // Ensure the "Existing" `id` field exists
    $this->assertEquals('Existing Location', $fields['id']['label']);
    $this->assertEquals('EntityRef', $fields['id']['input_type']);
    // FK fields should not be included
    $this->assertArrayNotHasKey('email_id', $fields);
    $this->assertArrayNotHasKey('email_2_id', $fields);
    // 1st and 2nd join fields should exist
    $this->assertEquals('Text', $fields['address_id.street_address']['input_type']);
    $this->assertEquals('Text', $fields['address_2_id.street_address']['input_type']);

  }

}
