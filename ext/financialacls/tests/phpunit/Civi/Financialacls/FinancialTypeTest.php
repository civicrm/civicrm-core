<?php

namespace Civi\Financialacls;

use Civi;
use CRM_Core_Session;

// I fought the Autoloader and the autoloader won.
require_once 'BaseTestClass.php';

/**
 * @group headless
 */
class FinancialTypeTest extends BaseTestClass {

  /**
   * Test that a message is put in session when changing the name of a
   * financial type.
   *
   * @throws \CRM_Core_Exception
   */
  public function testChangeFinancialTypeName(): void {
    Civi::settings()->set('acl_financial_type', TRUE);
    $type = $this->callAPISuccess('FinancialType', 'create', [
      'name' => 'my test',
    ]);
    $this->callAPISuccess('FinancialType', 'create', [
      'name' => 'your test',
      'id' => $type['id'],
    ]);
    $status = CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->assertEquals('Changing the name', substr($status[0]['text'], 0, 17));
  }

}
