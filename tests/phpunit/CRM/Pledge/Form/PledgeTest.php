<?php

use Civi\Api4\Email;

/**
 * CRM_Pledge_Form_PledgeTest
 *
 * @group headless
 */
class CRM_Pledge_Form_PledgeTest extends CiviUnitTestCase {

  /**
   * Test the post process function.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPostProcess(): void {
    $mut = new CiviMailUtils($this);
    $loggedInUser = $this->createLoggedInUser();
    $this->addLocationBlockToDomain();
    $this->swapMessageTemplateForInput('pledge_acknowledge', '{domain.name} {contact.first_name} {contact.email_greeting_display}');

    $form = $this->getFormObject('CRM_Pledge_Form_Pledge', [
      'amount' => 10,
      'contact_id' => $this->individualCreate(),
      'is_acknowledge' => 1,
      'start_date' => '2021-01-04',
      'create_date' => '2021-01-04',
      'from_email_address' => Email::get()
        ->addWhere('contact_id', '=', $loggedInUser)
        ->addSelect('id')->execute()->first()['id'],
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
      'installments' => 5,
      'currency' => 'USD',
      'scheduled_amount' => 10,
      'frequency_day' => 4,
      'status' => 'Pending',
    ]);
    $form->buildForm();
    $form->postProcess();
    $mut->checkAllMailLog([
      'Default Domain Name Anthony',
      'Dear Anthony',
    ]);
    $mut->clearMessages();
    $this->revertTemplateToReservedTemplate();
  }

}
