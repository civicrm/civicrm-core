<?php

use Civi\Api4\Email;

/**
 * CRM_Pledge_Form_PledgeTest
 *
 * @group headless
 */
class CRM_Pledge_Form_PledgeTest extends CiviUnitTestCase {

  public $_apiversion = 4;

  /**
   * Test the post process function.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPostProcess(): void {
    $loggedInUser = $this->createLoggedInUser();
    $this->addLocationBlockToDomain();
    $this->swapMessageTemplateForInput('pledge_acknowledge', '{domain.name} {contact.first_name} {contact.email_greeting_display}');

    $this->getTestForm('CRM_Pledge_Form_Pledge', [
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
    ])->processForm();
    $this->assertMailSentContainingStrings([
      'Default Domain Name Anthony',
      'Dear Anthony',
    ]);
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * A new pledge defaults to the Donation financial type, whatever it is labelled.
   *
   * @throws \CRM_Core_Exception
   */
  public function testDefaultFinancialTypeWithRelabelledDonation(): void {
    $this->relabelDonation('Spende');
    $form = $this->getFormObject('CRM_Pledge_Form_Pledge', [], ['action' => 'add']);
    $form->buildForm();
    $this->assertEquals(
      CRM_Core_PseudoConstant::getKey('CRM_Pledge_BAO_Pledge', 'financial_type_id', 'Donation'),
      $form->setDefaultValues()['financial_type_id']
    );
  }

  /**
   * @param string $label
   */
  private function relabelDonation(string $label): void {
    \Civi\Api4\FinancialType::update(FALSE)
      ->addWhere('name', '=', 'Donation')
      ->addValue('label', $label)
      ->execute();
  }

  protected function tearDown(): void {
    $this->relabelDonation('Donation');
    parent::tearDown();
  }

}
