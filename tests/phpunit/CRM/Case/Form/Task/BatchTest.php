<?php

use Civi\Api4\CustomGroup;
use Civi\Api4\UFGroup;
use Civi\Test\Invasive;

/**
 * Tests "Update multiple cases".
 *
 * @group headless
 */
class CRM_Case_Form_Task_BatchTest extends CiviCaseTestCase {

  /**
   * Removes the profile and custom group the test created.
   */
  public function tearDown(): void {
    if ($this->getTestEntityID('UFGroup', 'case_batch')) {
      UFGroup::delete(FALSE)->addWhere('id', '=', $this->getTestEntityID('UFGroup', 'case_batch'))->execute();
    }
    if ($this->getTestEntityID('CustomGroup', 'case_records')) {
      CustomGroup::delete(FALSE)->addWhere('id', '=', $this->getTestEntityID('CustomGroup', 'case_records'))->execute();
    }
    unset(Civi::$statics['UFFieldsFlat']);
    parent::tearDown();
  }

  /**
   * Multi-record ("Tab with table") case fields are left out of the grid.
   */
  public function testMultiRecordFieldsAreLeftOut(): void {
    $this->createTestEntity('CustomGroup', [
      'title' => 'Case records',
      'extends' => 'Case',
      'is_multiple' => TRUE,
      'style' => 'Tab with table',
    ], 'case_records');
    $recordFieldName = 'custom_' . $this->createTestEntity('CustomField', [
      'custom_group_id' => $this->getTestEntityID('CustomGroup', 'case_records'),
      'label' => 'Record title',
      'data_type' => 'String',
      'html_type' => 'Text',
    ])['id'];
    unset(Civi::$statics['UFFieldsFlat']);
    $ufGroupID = $this->createTestEntity('UFGroup', [
      'title' => 'Case batch profile',
      'is_active' => TRUE,
    ], 'case_batch')['id'];
    foreach (['case_subject' => 'Subject', $recordFieldName => 'Record title'] as $fieldName => $label) {
      $this->createTestEntity('UFField', [
        'uf_group_id' => $ufGroupID,
        'field_name' => $fieldName,
        'field_type' => 'Case',
        'label' => $label,
        'is_active' => TRUE,
      ], $fieldName);
    }
    CRM_Core_Session::singleton()->getStatus(TRUE);

    $form = $this->getTestForm('CRM_Case_Form_Search', [
      'radio_ts' => 'ts_sel',
      'task' => CRM_Core_Task::BATCH_UPDATE,
      CRM_Core_Form::CB_PREFIX . $this->getTestEntityID('Case', 'fixture') => 1,
    ])
      ->addSubsequentForm('CRM_Case_Form_Task_PickProfile', ['uf_group_id' => $ufGroupID])
      ->addSubsequentForm('CRM_Case_Form_Task_Batch')
      ->processForm();

    $batchForm = Invasive::get([$form, 'subsequentForms'])['Batch'];
    $this->assertSame(['case_subject'], array_keys(Invasive::get([$batchForm, '_fields'])));
    $this->assertContains(
      'Multi-record fields in the selected profile are not supported for Update multiple cases',
      array_column(CRM_Core_Session::singleton()->getStatus(TRUE), 'text')
    );
  }

}
