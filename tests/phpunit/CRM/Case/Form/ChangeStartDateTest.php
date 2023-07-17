<?php

/**
 * @group headless
 */
class CRM_Case_Form_ChangeStartDateTest extends CiviCaseTestCase {

  public function testChangeCaseStartDate(): void {
    $clientId = $this->individualCreate();
    $caseObj = $this->createCase($clientId, $this->getLoggedInUser());

    $result = $this->callAPISuccess('Case', 'getsingle', [
      'id' => $caseObj->id,
      'return' => ['start_date'],
    ]);
    $old_start_date = $result['start_date'];

    $activity_type_id = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Change Case Start Date');
    $_GET['atype'] = $_REQUEST['atype'] = $activity_type_id;
    $_GET['reset'] = $_REQUEST['reset'] = '1';
    $_GET['cid'] = $_REQUEST['cid'] = $clientId;
    $_GET['caseid'] = $_REQUEST['caseid'] = $caseObj->id;
    $_GET['action'] = $_REQUEST['action'] = 'add';

    // set new start date to 2 days ago
    $new_start_date = date('Y-m-d', strtotime('2 days ago'));

    $form = $this->getFormObject('CRM_Case_Form_Activity', [
      'activity_type_id' => $activity_type_id,
      'caseid' => $caseObj->id,
      'source_contact_id' => $this->getLoggedInUser(),
      'target_contact_id' => $clientId,
      'cid' => $clientId,
      'activity_date_time' => date('Y-m-d H:i:s'),
      'subject' => '',
      'start_date' => $new_start_date,
    ]);
    $form->set('caseid', $caseObj->id);
    $form->set('cid', $clientId);
    $form->set('atype', $activity_type_id);
    $form->buildForm();
    $form->postProcess();

    // Check start date was changed correctly.
    $result = $this->callAPISuccess('Case', 'getsingle', [
      'id' => $caseObj->id,
      'return' => ['start_date'],
    ]);
    $this->assertEquals($new_start_date, $result['start_date']);

    // Check activity got created with correct subject.
    $result = $this->callAPISuccess('Activity', 'getsingle', [
      'case_id' => $caseObj->id,
      'activity_type_id' => $activity_type_id,
      'return' => ['subject'],
    ]);
    $formatted_old_start_date = CRM_Utils_Date::customFormat($old_start_date, CRM_Core_Config::singleton()->dateformatFull);
    $formatted_new_start_date = CRM_Utils_Date::customFormat($new_start_date, CRM_Core_Config::singleton()->dateformatFull);
    $this->assertEquals("Change Case Start Date from {$formatted_old_start_date} to {$formatted_new_start_date}", $result['subject']);

    // Check open case activity was updated.
    $result = $this->callAPISuccess('Activity', 'getsingle', [
      'case_id' => $caseObj->id,
      'activity_type_id' => 'Open Case',
      'return' => ['activity_date_time'],
    ]);
    $this->assertEquals($new_start_date . ' 00:00:00', $result['activity_date_time']);
  }

}
