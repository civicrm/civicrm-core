<?php

/**
 * @group headless
 */
class CRM_Activity_BAO_ActivityTypeTest extends CiviUnitTestCase {

  /**
   * Test ActivityType
   */
  public function testActivityType() {
    $actParams = [
      'option_group_id' => 'activity_type',
      'name' => 'abc123',
      'label' => 'Write Unit Test',
      'is_active' => 1,
      'is_default' => 0,
    ];
    $result = $this->callAPISuccess('option_value', 'create', $actParams);

    $activity_type_id = $result['values'][$result['id']]['value'];

    // instantiate the thing we want to test and read it back
    $activityTypeObj = new CRM_Activity_BAO_ActivityType($activity_type_id);
    $keyValuePair = $activityTypeObj->getActivityType();

    $this->assertEquals($keyValuePair['machineName'], 'abc123');
    $this->assertEquals($keyValuePair['displayLabel'], 'Write Unit Test');

    // cleanup
    $this->callAPISuccess('option_value', 'delete', ['id' => $result['id']]);
  }

}
