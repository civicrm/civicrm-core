<?php

namespace E2E\AfformMock;

use Civi;
use Civi\Api4\Contact;
use Civi\Api4\Email;

/**
 * Perform some tests against `mockJoinIf.aff.html`.
 *
 * @group e2e
 */
class MockJoinIfBrowserTest extends Civi\Test\MinkBase {

  private string $lastName;

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()
      ->install(['org.civicrm.afform', 'org.civicrm.afform-mock'])
      ->apply();
  }

  /**
   * A join that af-if hides and shows again submits the value entered after it reappears.
   */
  public function testJoinShownAgainByAfIfSubmitsItsValue(): void {
    $this->lastName = uniqid('JoinIf');
    $session = $this->mink->getSession();
    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $this->visit(Civi::url('backend://civicrm/mock-join-if'));
    $this->assertTrue($session->wait(5000, 'document.querySelector("af-field[name=do_not_email] input") !== null'), 'Form loaded');
    $this->assertSession()->elementExists('css', 'af-field[name=last_name] input')->setValue($this->lastName);

    $doNotEmail = $this->assertSession()->elementExists('css', 'af-field[name=do_not_email] input[type=checkbox]');
    foreach ([TRUE, FALSE, TRUE, FALSE] as $checked) {
      $doNotEmail->click();
      $emailShown = $checked ? 'false' : 'true';
      $this->assertTrue($session->wait(5000, "(document.querySelector('af-field[name=email] input') !== null) === $emailShown"), "Email shown: $emailShown");
    }
    $this->assertSession()->elementExists('css', 'af-field[name=email] input')->setValue('kept@example.org');
    $this->assertSession()->elementExists('css', 'button.af-button')->click();

    $this->assertTrue($this->waitForContact(10), 'Submission saved the contact');
    $emails = Email::get(FALSE)
      ->addWhere('contact_id.last_name', '=', $this->lastName)
      ->execute()->column('email');
    $this->assertSame(['kept@example.org'], $emails);
  }

  private function waitForContact(int $seconds): bool {
    for ($i = 0; $i < $seconds * 4; $i++) {
      if (Contact::get(FALSE)->addWhere('last_name', '=', $this->lastName)->selectRowCount()->execute()->countMatched()) {
        return TRUE;
      }
      usleep(250000);
    }
    return FALSE;
  }

  public function tearDown(): void {
    if (isset($this->lastName)) {
      Contact::delete(FALSE)->addWhere('last_name', '=', $this->lastName)->setUseTrash(FALSE)->execute();
    }
    parent::tearDown();
  }

}
