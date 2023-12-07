<?php
/**
 * Class CRM_Case_Form_CaseViewTest
 * @group headless
 */
class CRM_Case_Form_CaseViewTest extends CiviCaseTestCase {

  /**
   * Test that the search filter dropdown includes the desired activity types.
   */
  public function testSearchFilterDropdown(): void {
    $client_id = $this->individualCreate([], 0, TRUE);
    $caseObj = $this->createCase($client_id, $this->getLoggedInUser());
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
      'access my cases and activities',
    ];

    $form = $this->getFormObject('CRM_Case_Form_CaseView');
    $form->set('cid', $client_id);
    $form->set('id', $caseObj->id);
    $form->buildForm();
    $options = $form->getElement('activity_type_filter_id')->_options;
    // We don't care about the first one, just check it's what we expect
    $this->assertEquals('- select activity type -', $options[0]['text']);
    unset($options[0]);
    $mappedOptions = array_map(function($v) {
      return [$v['attr']['value'] => $v['text']];
    }, $options);
    $this->assertEquals([
      [14 => 'Follow up'],
      [61 => 'Income and benefits stabilization'],
      [59 => 'Long-term housing plan'],
      [56 => 'Medical evaluation'],
      [57 => 'Mental health evaluation'],
      [13 => 'Open Case'],
      [58 => 'Secure temporary housing'],
    ], array_values($mappedOptions));

    // Now add some activities where the type might not even be in the config.
    $this->callAPISuccess('Activity', 'create', [
      'case_id' => $caseObj->id,
      'subject' => 'aaaa',
      'activity_type_id' => 'Inbound Email',
    ]);
    $this->callAPISuccess('Activity', 'create', [
      'case_id' => $caseObj->id,
      'subject' => 'bbbb',
      'activity_type_id' => 'Email',
    ]);
    $this->callAPISuccess('Activity', 'create', [
      'case_id' => $caseObj->id,
      'subject' => 'cccc',
      'activity_type_id' => 'Meeting',
    ]);

    // And let's delete one since we still want it to be available as a filter
    $this->callAPISuccess('Activity', 'create', [
      'case_id' => $caseObj->id,
      'subject' => 'dddd',
      'activity_type_id' => 'Phone Call',
      'is_deleted' => 1,
    ]);

    $form = $this->getFormObject('CRM_Case_Form_CaseView');
    $form->set('cid', $client_id);
    $form->set('id', $caseObj->id);
    $form->buildForm();
    $options = $form->getElement('activity_type_filter_id')->_options;
    unset($options[0]);
    $mappedOptions = array_map(function($v) {
      return [$v['attr']['value'] => $v['text']];
    }, $options);
    $this->assertEquals([
      [3 => 'Email'],
      [14 => 'Follow up'],
      [12 => 'Inbound Email'],
      [61 => 'Income and benefits stabilization'],
      [59 => 'Long-term housing plan'],
      [56 => 'Medical evaluation'],
      [1 => 'Meeting'],
      [57 => 'Mental health evaluation'],
      [13 => 'Open Case'],
      [2 => 'Phone Call'],
      [58 => 'Secure temporary housing'],
    ], array_values($mappedOptions));
  }

}
