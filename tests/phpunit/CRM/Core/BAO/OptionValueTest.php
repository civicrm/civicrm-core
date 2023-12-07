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

use Civi\Api4\OptionValue;

/**
 * Class CRM_Core_BAO_SchemaHandlerTest.
 *
 * These tests create and drop indexes on the civicrm_uf_join table. The indexes
 * being added and dropped we assume will never exist.
 * @group headless
 */
class CRM_Core_BAO_OptionValueTest extends CiviUnitTestCase {

  /**
   * Test setup for every test.
   */
  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * Test that option values that support more than one default maintain them.
   *
   * There is code to support updating default = 0 for other options
   * when an option value is set to 1. However, some option groups (eg.
   * email_greeting) support multiple option values.
   *
   * @throws \CRM_Core_Exception
   */
  public function testHandlingForMultiDefaultOptions(): void {
    foreach (['email_greeting', 'postal_greeting', 'addressee'] as $optionGroupName) {
      $options = $this->getDefaultOptions($optionGroupName);
      $existing = count($options);
      OptionValue::update()->addWhere('id', 'IN', array_keys($options))
        ->setValues(['description', '=', 'updated', 'is_default' => TRUE])
        ->execute();
      $options = $this->getDefaultOptions($optionGroupName);
      $this->assertCount($existing, $options, 'There should be no change in the number of default options');
    }
  }

  /**
   * Test that option values that support more than one default maintain them.
   *
   * The from_email_address supports a single default per domain.
   *
   * @throws \CRM_Core_Exception
   */
  public function testDefaultHandlingForFromEmailAddress(): void {
    $options = $this->getDefaultOptions('from_email_address');
    $this->assertCount(2, $options);
    OptionValue::create()
      ->setValues([
        'option_group_id:name' => 'from_email_address',
        'is_default' => TRUE,
        'label' => 'email@example.com',
        'name' => 'email@example.com',
        'value' => 3,
      ])
      ->execute();

    $options = $this->getDefaultOptions('from_email_address');
    $this->assertCount(2, $options, 'There should be one default per domain');
  }

  /**
   * Ensure only one option value exists after calling ensureOptionValueExists.
   */
  public function testEnsureOptionValueExistsExistingValue(): void {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists(['name' => 'Completed', 'option_group_id' => 'contribution_status']);
    $this->callAPISuccessGetSingle('OptionValue', ['name' => 'Completed', 'option_group_id' => 'contribution_status']);
  }

  /**
   * Ensure only one option value exists adds a new value.
   */
  public function testEnsureOptionValueExistsNewValue(): void {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists(['name' => 'Bombed', 'option_group_id' => 'contribution_status']);
    $optionValues = $this->callAPISuccess('OptionValue', 'get', ['option_group_id' => 'contribution_status']);
    foreach ($optionValues['values'] as $value) {
      if ($value['name'] === 'Bombed') {
        return;
      }
    }
    $this->fail('Should not have gotten this far');
  }

  /**
   * Ensure only one option value copes with disabled.
   *
   * (Our expectation is no change - ie. currently we are respecting 'someone's
   * decision to disable it & leaving it in that state.
   */
  public function testEnsureOptionValueExistsDisabled(): void {
    $optionValue = CRM_Core_BAO_OptionValue::ensureOptionValueExists(['name' => 'Crashed', 'option_group_id' => 'contribution_status', 'is_active' => 0]);
    $value = $this->callAPISuccessGetSingle('OptionValue', ['name' => 'Crashed', 'option_group_id' => 'contribution_status']);
    $this->assertEquals(0, $value['is_active']);
    $this->assertEquals($value['id'], $optionValue['id']);

    $optionValue = CRM_Core_BAO_OptionValue::ensureOptionValueExists(['name' => 'Crashed', 'option_group_id' => 'contribution_status']);
    $value = $this->callAPISuccessGetSingle('OptionValue', ['name' => 'Crashed', 'option_group_id' => 'contribution_status']);
    $this->assertEquals(0, $value['is_active']);
    $this->assertEquals($value['id'], $optionValue['id']);
  }

  /**
   * @param string $optionGroupName
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function getDefaultOptions(string $optionGroupName): array {
    $options = (array) OptionValue::get()
      ->addWhere('option_group_id:name', '=', $optionGroupName)
      ->addWhere('is_default', '=', TRUE)
      ->execute()->indexBy('id');
    return $options;
  }

}
