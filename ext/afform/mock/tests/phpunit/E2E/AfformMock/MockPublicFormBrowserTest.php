<?php

namespace E2E\AfformMock;

use Civi;

/**
 * Perform some tests against `mockPublicForm.aff.html`.
 *
 * This test uses Chrome and checks more high-level behaviors. For lower-level checks,
 * see MockPublicFormTest.
 *
 * @group e2e
 */
class MockPublicFormBrowserTest extends Civi\Test\MinkBase {

  protected $formName = 'mockPublicForm';

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()
      ->install(['org.civicrm.afform', 'org.civicrm.afform-mock'])
      ->apply();
  }

  /**
   * Create a contact with middle name "Donald". Use the custom form to change the middle
   * name to "Donny".
   *
   * @return void
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \CRM_Core_Exception
   */
  public function testUpdateMiddleName() {
    $donny = $this->initializeTheodoreKerabatsos();
    $this->assertEquals('Donald', $this->getContact($donny)['middle_name'], 'Middle name has original value');

    $session = $this->mink->getSession();
    $url = $this->renderToken('{form.mockPublicFormUrl}', $donny);
    $this->visit($url);

    // Goal: Wait for the fields to be populated. But how...?
    // $session->wait(5000, 'document.querySelectorAll("input#middle-name-1").length > 0');
    // $session->wait(5000, '!!document.querySelectorAll("input#first-name-0").length && !!document.querySelectorAll("input#first-name-0")[0].value');
    // $session->wait(5000, '!!document.querySelectorAll("input#middle-name-1").length && document.querySelectorAll("input#middle-name-1")[0].value.length > 0');
    // $session->wait(5000, 'CRM.$(\'#middle-name-1:contains("Donald")\').length > 0');
    // $session->wait(5000, 'window.CRM.$(\'#middle-name-1:contains("Donald")\').length > 0');
    $session->wait(2000); /* FIXME: Cannot get wait-condition to be meaningfully used */

    $middleName = $this->assertSession()->elementExists('css', 'input#middle-name-1');
    $this->assertEquals('Donald', $middleName->getValue());
    $middleName->setValue('Donny');

    $submit = $this->assertSession()->elementExists('css', 'button.af-button.btn-primary');
    $submit->click();

    // Goal: Wait for the "Saved" status message. But how...?
    // $session->wait(5000, 'document.querySelectorAll(".crm-status-box-msg").length > 0');
    // $session->wait(5000, 'CRM.$(\'.crm-status-box-msg:contains("Saved")\').length > 1');
    $session->wait(2000); /* FIXME: Cannot get wait-condition to be meaningfully used */

    $this->assertEquals('Donny', $this->getContact($donny)['middle_name'], 'Middle name has been updated');
  }

  protected function renderToken(string $token, int $cid): string {
    $tp = new \Civi\Token\TokenProcessor(\Civi::dispatcher(), []);
    $tp->addRow()->context('contactId', $cid);
    $tp->addMessage('example', $token, 'text/plain');
    $tp->evaluate();
    return $tp->getRow(0)->render('example');
  }

  protected function initializeTheodoreKerabatsos(): int {
    $record = [
      'contact_type' => 'Individual',
      'first_name' => 'Theodore',
      'middle_name' => 'Donald',
      'last_name' => 'Kerabatsos',
      'external_identifier' => __CLASS__,
    ];
    $contact = \Civi\Api4\Contact::save(FALSE)
      ->setRecords([$record])
      ->setMatch(['external_identifier'])
      ->execute();
    return $contact[0]['id'];
  }

  /**
   * @param int $contactId
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected function getContact(int $contactId): array {
    return Civi\Api4\Contact::get(FALSE)
      ->addWhere('id', '=', $contactId)
      ->addSelect('id', 'first_name', 'middle_name', 'last_name')
      ->execute()
      ->single();
  }

}
