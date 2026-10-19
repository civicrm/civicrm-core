<?php

/**
 * @group headless
 */
class CRM_Case_Form_ChangeStartDateTest extends CiviCaseTestCase {

  public $_apiversion = 4;

  public function testChangeCaseStartDate(): void {
    $clientId = $this->individualCreate();
    $caseObj = $this->createCase($clientId, $this->getLoggedInUser());

    $result = $this->callAPISuccess('Case', 'getsingle', [
      'id' => $caseObj->id,
      'return' => ['start_date'],
    ]);
    $old_start_date = $result['start_date'];

    $activity_type_id = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Change Case Start Date');

    // set new start date to 2 days ago
    $new_start_date = date('Y-m-d H:i:s', strtotime('2 days ago'));

    $this->getTestForm('CRM_Case_Form_Activity', [
      'activity_type_id:name' => 'Change Case Start Date',
      'caseid' => $caseObj->id,
      'source_contact_id' => $this->getLoggedInUser(),
      'target_contact_id' => $clientId,
      'cid' => $clientId,
      'activity_date_time' => date('Y-m-d H:i:s'),
      'subject' => '',
      'start_date' => $new_start_date,
      'allowRepeatConfigToSubmit' => TRUE,
    ], [
      'atype' => $activity_type_id,
      'reset' => '1',
      'cid' => $clientId,
      'caseid' => $caseObj->id,
      'action' => 'add',
    ])->processForm();

    // Check start date was changed correctly.
    $result = $this->callAPISuccess('Case', 'getsingle', [
      'id' => $caseObj->id,
      'return' => ['start_date'],
    ]);
    $this->assertEquals($new_start_date, $result['start_date']);

    // Check activity got created with correct subject.
    $result = $this->callAPISuccess('Activity', 'getsingle', [
      'case_id' => $caseObj->id,
      'activity_type_id:name' => 'Change Case Start Date',
      'return' => ['subject'],
    ]);
    $formatted_old_start_date = CRM_Utils_Date::customFormat($old_start_date, CRM_Core_Config::singleton()->dateformatFull);
    $formatted_new_start_date = CRM_Utils_Date::customFormat($new_start_date, CRM_Core_Config::singleton()->dateformatFull);
    $this->assertEquals("Change Case Start Date from {$formatted_old_start_date} to {$formatted_new_start_date}", $result['subject']);

    // Check open case activity was updated.
    $result = $this->callAPISuccess('Activity', 'getsingle', [
      'case_id' => $caseObj->id,
      'activity_type_id:name' => 'Open Case',
      'return' => ['activity_date_time'],
    ]);
    $this->assertEquals($new_start_date, $result['activity_date_time']);
  }

}
