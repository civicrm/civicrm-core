<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Afform;

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\EntityTrait;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CustomGroup GetAfforms action.
 *
 * @group headless
 */
class GetAfformsTest extends TestCase implements HeadlessInterface {

  use EntityTrait;

  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()
      ->installMe(__DIR__)
      ->install('org.civicrm.search_kit')
      ->apply();
  }

  public function tearDown(): void {
    if (!empty($this->ids['CustomGroup'])) {
      CustomGroup::delete(FALSE)
        ->addWhere('id', 'IN', array_values($this->ids['CustomGroup']))
        ->execute();
    }
    parent::tearDown();
  }

  /**
   * Test that a serialized Float (Number) custom field with options and a default value
   * does not cause an array_flip warning in GetAfforms.
   */
  public function testSerializedFloatCustomFieldDefaultValue(): void {
    $customGroup = $this->createTestEntity('CustomGroup', [
      'title' => 'Test Custom Group',
      'name' => 'test_custom_group',
      'extends' => 'Individual',
    ]);

    $customField = $this->createTestEntity('CustomField', [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Float Multi Select',
      'name' => 'float_multi_select',
      'data_type' => 'Float',
      'html_type' => 'Select',
      'serialize' => 1,
      'option_values' => [
        '1' => 'One',
        '2' => 'Two',
      ],
    ]);

    // Update CustomField with default value as done in Custom Field settings
    CustomField::update(FALSE)
      ->addWhere('id', '=', $customField['id'])
      ->addValue('default_value', \CRM_Core_DAO::serializeField(['1'], 1))
      ->execute();

    $afforms = CustomGroup::getAfforms(FALSE)
      ->addWhere('id', '=', $customGroup['id'])
      ->setGetLayout(TRUE)
      ->execute();

    if ($afforms->getErrors()) {
      $this->fail('GetAfforms failed with error: ' . print_r($afforms->getErrors(), TRUE));
    }
    $forms = $afforms->single()['forms'];
    $this->assertNotEmpty($forms);
    $this->assertStringContainsString('One', $forms[0]['layout']);
  }

}
