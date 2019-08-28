<?php

namespace E2E\Core;

/**
 * Class UserOverrideTest
 *
 * Check that overriding session user behaves as expected.
 *
 * @package E2E\Core
 * @group e2e
 */
class UserOverrideTest extends \CiviEndToEndTestCase {

  protected function setUp() {
    parent::setUp();
  }

  protected function tearDown() {
    while (\CRM_Core_Session::singleton()->restoreCurrentUser()) {
      // Loop until all overrides are cleared
    }
  }

  public function testOverride() {
    $session = \CRM_Core_Session::singleton();
    $originalUser = $session::getLoggedInContactID();
    // Set user CID to 2
    $o1 = $session->overrideCurrentUser(2);
    $this->assertEquals(2, $session::getLoggedInContactID());
    // Set user CID to 3
    $o2 = $session->overrideCurrentUser(3);
    $this->assertEquals(3, $session::getLoggedInContactID());
    // Set user CID to 4
    $o3 = $session->overrideCurrentUser(4);
    // Clear the second override
    $this->assertTrue($session->restoreCurrentUser($o2));
    // Latest override should still stand
    $this->assertEquals(4, $session::getLoggedInContactID());
    // Clear the last override, should revert to the one remaining override
    $this->assertTrue($session->restoreCurrentUser());
    $this->assertEquals(2, $session::getLoggedInContactID());
    $this->assertEquals(2, $session->getOverriddenUser());
    // Clear the final override
    $this->assertTrue($session->restoreCurrentUser());
    $this->assertEquals($originalUser, $session::getLoggedInContactID());
    // Assert there are no overrides left to clear
    $this->assertNull($session->getOverriddenUser());
    $this->assertFalse($session->restoreCurrentUser());
  }

}
