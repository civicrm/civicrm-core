<?php

/**
 * Class CRM_Utils_TypeTest
 * @package CiviCRM
 * @subpackage CRM_Utils_Type
 * @group headless
 */
class CRM_Utils_Check_Component_OptionGroupsTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function testCheckOptionGroupValues() {
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

}
