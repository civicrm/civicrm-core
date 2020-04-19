<?php

/**
 * Class CiviEndToEndTestCase
 *
 * An end-to-end test case in which we have booted Civi+CMS and loaded the
 * admin user in the local process.
 *
 * Note: If you need to work as a different user, try using `cv()` or
 * a web-service.
 */
class CiviEndToEndTestCase extends PHPUnit\Framework\TestCase implements \Civi\Test\EndToEndInterface {

  public static function setUpBeforeClass() {
    CRM_Core_Config::singleton(1, 1);
    CRM_Utils_System::loadBootStrap(array(
      'name' => $GLOBALS['_CV']['ADMIN_USER'],
      'pass' => $GLOBALS['_CV']['ADMIN_PASS'],
    ));
    CRM_Utils_System::synchronizeUsers();

    parent::setUpBeforeClass();
  }

  protected function tearDown() {
    $result = db_query_range('SELECT * FROM {watchdog} ORDER BY wid DESC', 0, 1);
    foreach ($result as $r) {
      if ($r->type === 'page not found') {
        echo __CLASS__ . ": I didn't do it.\n";
        watchdog('crm_e2e', 'entry to just prevent next test from triggering false positive');
      }
    }
  }

}
