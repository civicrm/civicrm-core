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
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testPostProcess(): void {
    $mut = new CiviMailUtils($this);
    $loggedInUser = $this->createLoggedInUser();
    $this->addLocationBlockToDomain();
    $this->swapMessageTemplateForInput('pledge_acknowledge', '{domain.name} {contact.first_name}');

    $form = $this->getFormObject('CRM_Pledge_Form_Pledge', [
      'amount' => 10,
      'installments' => 1,
      'contact_id' => $this->individualCreate(),
      'is_acknowledge' => 1,
      'start_date' => '2021-01-04',
      'create_date' => '2021-01-04',
      'from_email_address' => Email::get()
        ->addWhere('contact_id', '=', $loggedInUser)
        ->addSelect('id')->execute()->first()['id'],
    ]);
    $form->buildForm();
    $form->postProcess();
    $mut->checkAllMailLog([
      'Default Domain Name Anthony',
      123,
      'fixme.domainemail@example.org',
      '<p>Dear Anthony,</p>',
    ]);
    $mut->clearMessages();
    $this->revertTemplateToReservedTemplate('pledge_acknowledge');
  }

}
