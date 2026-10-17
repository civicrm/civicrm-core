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
 * @group headless
 * @group queue
 */
class CRM_Queue_ErrorPolicyTest extends CiviUnitTestCase {

  /**
   * A PHP error in a queue task is reported as a JSON error, and the error handler is restored.
   */
  public function testCallReportsPhpError(): void {
    $handler = set_error_handler(NULL);
    restore_error_handler();
    try {
      (new CRM_Queue_ErrorPolicy())->call(function () {
        throw new TypeError('Ruh-roh');
      });
      $this->fail('Expected the error to be reported');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(1, $e->errorData['is_error']);
      $this->assertEquals(0, $e->errorData['is_continue']);
      $this->assertStringContainsString('Ruh-roh', $e->errorData['exception']);
    }
    $this->assertSame($handler, set_error_handler(NULL));
    restore_error_handler();
  }

}
