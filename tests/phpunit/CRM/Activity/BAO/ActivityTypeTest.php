<?php

/**
 * @group headless
 */
class CRM_Activity_BAO_ActivityTypeTest extends CiviUnitTestCase {

  /**
   * API version in use.
   *
   * @var int
   */
  protected $_apiversion = 4;

  /**
   * Test ActivityType
   */
  public function testActivityType(): void {
    $actParams = [
      'option_group_id' => 'activity_type',
      'name' => 'abc123',
      'label' => 'Write Unit Test',
      'is_active' => 1,
      'is_default' => 0,
    ];
    $result = $this->callAPISuccess('OptionValue', 'create', $actParams);

    $activity_type_id = $result['values'][$result['id']]['value'];

    // instantiate the thing we want to test and read it back
    $activityTypeObj = new CRM_Activity_BAO_ActivityType($activity_type_id);
    $keyValuePair = $activityTypeObj->getActivityType();

    $this->assertEquals('abc123', $keyValuePair['machineName']);
    $this->assertEquals('Write Unit Test', $keyValuePair['displayLabel']);

    // cleanup
    $this->callAPISuccess('option_value', 'delete', ['id' => $result['id']]);
  }

}
