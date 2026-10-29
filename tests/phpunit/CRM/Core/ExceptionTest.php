<?php

/**
 * @group headless
 */
class CRM_Core_ExceptionTest extends CiviUnitTestCase {

  public function testPreviousException(): void {
    $previous = new RuntimeException('Cause');
    $exception = new CRM_Core_Exception('Failed', 7, [], $previous);

    $this->assertSame($previous, $exception->getPrevious());
    $this->assertEquals('Failed', $exception->getMessage());
    $this->assertEquals(7, $exception->getCode());
  }

  public function testPreviousExceptionWithDatabaseError(): void {
    $previous = new RuntimeException('Cause');
    $dbError = new DB_Error(DB_ERROR_NOSUCHTABLE);
    $exception = new CRM_Core_Exception('Failed', 0, ['exception' => $dbError], $previous);

    $this->assertSame($previous, $exception->getPrevious());
    $this->assertSame($dbError, $exception->getCause());
  }

}
