<?php

/**
 * Class CRM_Utils_TypeTest
 * @package CiviCRM
 * @subpackage CRM_Utils_Type
 * @group headless
 */
class CRM_Utils_Check_Component_OptionGroupsTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  public function testCheckOptionGroupValues(): void {
    $optionGroup = $this->callAPISuccess('OptionGroup', 'create', [
      'name' => 'testGroup',
      'title' => 'testGroup',
      'data_type' => 'Integer',
    ]);
    // test that zero is a valid integer.
    $this->callAPISuccess('OptionValue', 'create', [
      'option_group_id' => $optionGroup['id'],
      'label' => 'zero',
      'value' => 0,
    ]);
    $check = new \CRM_Utils_Check_Component_OptionGroups();
    $result = $check->checkOptionGroupValues();
    $this->assertArrayNotHasKey(0, $result);
  }

  /**
   * Mismatching values are listed in an escaped table.
   */
  public function testCheckOptionGroupValuesListsMismatch(): void {
    $optionGroup = $this->callAPISuccess('OptionGroup', 'create', [
      'name' => 'testGroup',
      'title' => 'Test <group>',
      'data_type' => 'Integer',
    ]);
    $this->callAPISuccess('OptionValue', 'create', [
      'option_group_id' => $optionGroup['id'],
      'label' => 'Not <a> number',
      'value' => 'abc',
    ]);
    $check = new \CRM_Utils_Check_Component_OptionGroups();
    $result = $check->checkOptionGroupValues();
    $this->assertStringContainsString('<tr><td>Test &lt;group&gt;</td><td>Not &lt;a&gt; number</td></tr>', $result[0]->getMessage());
  }

}
