<?php

namespace E2E\AfformMock;

use Civi;

/**
 * Perform some tests against `mockLoadedValues.aff.html`.
 *
 * @group e2e
 */
class MockLoadedValuesBrowserTest extends Civi\Test\MinkBase {

  use Civi\Test\Api4TestTrait;

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()
      ->install(['org.civicrm.afform', 'org.civicrm.afform-mock'])
      ->apply();
  }

  /**
   * A field behind af-if that opens after prefill shows the loaded value, not its default.
   */
  public function testPrefilledValueBehindAfIf(): void {
    $contact = $this->createTestRecord('Individual', [
      'first_name' => 'Ada',
      'last_name' => 'Lovelace',
    ]);
    $session = $this->mink->getSession();
    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $this->visit(Civi::url('backend://civicrm/mock-loaded-values')->addFragmentQuery(['Individual1' => $contact['id']]));
    $this->assertTrue($session->wait(5000, 'document.querySelector("af-field[name=first_name] input")?.value === "Ada"'), 'Prefilled first name');
    $this->assertTrue($session->wait(5000, 'document.querySelector("af-field[name=last_name] input")?.value === "Lovelace"'), 'Prefilled last name');
  }

  /**
   * A copied repeat item keeps the copied values, not the defaults.
   */
  public function testCopiedValue(): void {
    $session = $this->mink->getSession();
    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $this->visit(Civi::url('backend://civicrm/mock-loaded-values'));
    $this->assertTrue($session->wait(5000, 'document.querySelector("af-field[name=nick_name] input")?.value === "Default"'), 'Default nick name');
    $this->assertSession()->elementExists('css', 'af-field[name=nick_name] input')->setValue('Copied');
    $this->assertSession()->elementExists('css', 'button.af-repeat-copy-btn')->click();
    $this->assertTrue($session->wait(5000, 'document.querySelectorAll("af-field[name=nick_name] input")[1]?.value === "Copied"'), 'Copied nick name');
  }

  public function tearDown(): void {
    $this->deleteTestRecords();
    parent::tearDown();
  }

}
