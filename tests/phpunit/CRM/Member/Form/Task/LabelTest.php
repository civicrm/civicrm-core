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

/**
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Member_Form_Task_LabelTest extends CiviUnitTestCase {

  /**
   * Test print label.
   *
   * This is a 'no error' test to support refactoring. It ensures no fatal is hit & lays
   * the basis for more tests later.
   */
  public function testMembershipTokenReplacementInPDF(): void {
    // First check tasks are there is some weird static caching that could mess us up.
    $tasks = CRM_Member_Task::tasks();
    $this->assertArrayHasKey(201, $tasks, print_r($tasks, TRUE));
    $tasks = CRM_Member_Task::permissionedTaskTitles(CRM_Core_Permission::EDIT);
    $this->assertArrayHasKey(201, $tasks);
    $membershipID = $this->contactMembershipCreate(['contact_id' => $this->individualCreate()]);
    /* @var CRM_Member_Form_Task_Label $form */
    $form = $this->getFormObject('CRM_Member_Form_Task_Label', [
      'task' => 201,
      'radio_ts' => 'ts_sel',
    ], 'Search');

    $_SESSION['_' . $form->controller->_name . '_container']['values']['Label'] = [
      'location_type_id' => NULL,
      'label_name' => 3475,
    ];
    $form->preProcess();
    $form->ids = [$membershipID => $membershipID];
    $form->buildForm();
    try {
      $form->postProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(3475, $e->errorData['format']);
    }
  }

}
