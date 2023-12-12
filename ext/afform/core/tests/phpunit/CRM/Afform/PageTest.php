<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class CRM_Afform_PageTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testNotesTab(): void {
    // temporarily be more error-y
    set_error_handler(
      function(int $errno, string $errstr, string $errfile, int $errline) {
        throw new \ErrorException($errstr);
      },
      E_ALL
    );
    $errorToThrow = NULL;
    try {
      $result = \Civi\Api4\SearchDisplay::run()
        ->setReturn('page:1')
        ->setSavedSearch('Contact_Summary_Notes')
        ->setDisplay('Contact_Summary_Notes_Tab')
        ->setAfform('afsearchTabNote')
        ->setFilters([
          'entity_id' => 1,
          'entity_table' => 'civicrm_contact',
        ])->execute();
      $this->assertNotEmpty($result->toolbar[0]);
    }
    catch (\ErrorException $e) {
      $errorToThrow = $e;
    }
    finally {
      // make sure to remove our handler no matter what happens
      restore_error_handler();
    }
    if ($errorToThrow) {
      throw $errorToThrow;
    }
  }

}
