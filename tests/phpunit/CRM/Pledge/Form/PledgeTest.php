<?php

use Civi\Api4\Email;

/**
 *  Include dataProvider for tests
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
    $this->swapMessageTemplateForInput('pledge_acknowledge', '{domain.name} {contact.first_name}');

    $form = $this->getFormObject('CRM_Pledge_Form_Pledge', [
      'amount' => 10,
      'installments' => 1,
      'contact_id' => $this->individualCreate(),
      'is_acknowledge' => 1,
      'from_email_address' => Email::get()
        ->addWhere('contact_id', '=', $loggedInUser)
        ->addSelect('id')->execute()->first()['id'],
    ]);
    $form->buildForm();
    $form->postProcess();
    $mut->checkAllMailLog(['Default Domain Name Anthony']);
    $mut->clearMessages();
    $this->revertTemplateToReservedTemplate('pledge_acknowledge');
  }

}
