<?php

namespace E2E\Core;

/**
 * Class HookTest
 * @package E2E\Core
 * @group e2e
 */
class HookTest extends \CiviEndToEndTestCase {

  /**
   * This test ensures that CRM_Utils_Hook::invoke() dispatches via Symfony.
   *
   * This should be *in*addition* to dispatching through the UF event system,
   * although the mechanics depend on the UF, so that part has to be tested per-UF.
   *
   * This uses the canonical form, `CRM_Utils_Hook::invoke(string[] $names...)`
   */
  public function testSymfonyListener_names() {
    $calls = 0;
    $hookExample = function ($e) use (&$calls) {
      $calls++;
      $e->a['foo'] = 'a.name';
      $e->b->bar = 'b.name';
    };
    \Civi::dispatcher()->addListener('hook_civicrm_e2eHookExample', $hookExample);
    try {
      $a = [];
      $b = new \stdClass();
      $this->hookStub(['a', 'b'], $a, $b);
      $this->assertEquals(1, $calls);
      $this->assertEquals('a.name', $a['foo']);
      $this->assertEquals('b.name', $b->bar);
    }
    finally {
      \Civi::dispatcher()->removeListener('hook_civicrm_e2eHookExample', $hookExample);
    }
  }

  /**
   * @param mixed $names
   * @param array $a
   * @param \stdClass $b
   * @return mixed
   */
  private function hookStub($names, &$a, $b) {
    return \CRM_Utils_Hook::singleton()
      ->invoke($names, $a, $b, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject,
        'civicrm_e2eHookExample');
  }

}
