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

use Civi\Api4\Email;

/**
 *  Test Email task.
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Contribute_Form_Task_PDFTest extends CiviUnitTestCase {

  /**
   * Clean up after test.
   */
  protected function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test the send pdf task filters out contacts who should not receive the
   * receipt.
   *
   * @throws \API_Exception
   */
  public function testSendPDF(): void {
    $variants = [[], ['do_not_email' => TRUE], ['email' => ''], ['is_deceased' => TRUE], ['on_hold' => 1]];
    $searchValues = [
      'task' => CRM_Core_Task::PDF_LETTER,
      'radio_ts' => 'ts_sel',
    ];
    foreach ($variants as $variant) {
      $contactID = $this->individualCreate($variant);
      $contributionID = $this->contributionCreate(['contact_id' => $contactID]);
      $searchValues['mark_x_' . $contributionID] = 1;
      if (!empty($variant['on_hold'])) {
        Email::update()
          ->addWhere('contact_id', '=', $contactID)
          ->setValues(['on_hold' => TRUE])->execute();
      }
    }

    $form = $this->getFormObject('CRM_Contribute_Form_Task_PDF', [
      'receipt_update' => 1,
    ], NULL, $searchValues);
    $form->buildForm();
    $form->postProcess();
    $status = CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->assertEquals([
      'text' => 'Email was NOT sent to 4 contacts (no email address on file, or communication preferences specify DO NOT EMAIL, or contact is deceased).',
      'title' => 'Email Error',
      'type' => 'error',
      'options' => NULL,
    ], $status[0]);
  }

}
