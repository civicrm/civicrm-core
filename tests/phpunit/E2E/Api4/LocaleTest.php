<?php

namespace E2E\Api4;

/**
 * Class LocaleTest
 * @package E2E\Api4
 * @group e2e
 */
class LocaleTest extends \CiviEndToEndTestCase {

  /**
   * Ensure that
   */
  public function testSetLanguage() {
    $contacts = civicrm_api4('Contact', 'get', [
      'limit' => 25,
      'language' => 'en_US',
    ]);
    $this->assertTrue(count($contacts) > 0);
  }

}
