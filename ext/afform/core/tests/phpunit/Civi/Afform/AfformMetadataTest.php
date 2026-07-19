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

  public function testSuffixedFieldMeta():void {
    $suffixedFieldMeta = FormDataModel::getField('Individual', 'communication_style_id:name', 'create');

    $this->assertEquals($suffixedFieldMeta['data_type'], 'String');

    // check there are options
    $options = $suffixedFieldMeta['options'];
    $this->assertTrue(count($options) >= 2);

    // check the names have been returned as option IDs
    $optionIds = \array_map(fn ($option) => $option['id'], $options);
    $this->assertTrue(\in_array('formal', $optionIds));
  }

  public function testEntityRefSelectOptions(): void {
    $doc = \phpQuery::newDocumentHTML('<af-field name="employer_id"></af-field>');
    $afField = $doc->find('af-field')->get(0);
    $afField->setAttribute('defn', \CRM_Utils_JS::writeObject(['input_type' => 'Select'], TRUE));
    $fieldInfo = [
      'input_type' => 'EntityRef',
      'fk_entity' => 'Contact',
      'data_type' => 'Integer',
    ];
    $entities = [
      'Individual1' => [
        'type' => 'Individual',
        'label' => 'Individual 1',
      ],
      'Organization1' => [
        'type' => 'Organization',
        'label' => 'Organization 1',
      ],
      'Activity1' => [
        'type' => 'Activity',
        'label' => 'Activity 1',
      ],
    ];
    AfformMetadataInjector::setFieldMetadata($afField, $fieldInfo, $entities);
    $defn = \CRM_Utils_JS::getRawProps($afField->getAttribute('defn'));
    $options = \CRM_Utils_JS::decode($defn['options']);
    $this->assertCount(2, $options);
    $this->assertEquals('Individual1', $options[0]['id']);
    $this->assertEquals('Individual 1', $options[0]['label']);
    $this->assertEquals('Organization1', $options[1]['id']);
    $this->assertEquals('Organization 1', $options[1]['label']);
  }

}
