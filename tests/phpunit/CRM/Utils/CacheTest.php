<?php

/**
 * Class CRM_Utils_CacheTest
 * @group headless
 */
class CRM_Utils_CacheTest extends CiviUnitTestCase {

  public function testNack() {
    $values = [];
    for ($i = 0; $i < 5; $i++) {
      $nack = CRM_Utils_Cache::nack();
      $this->assertRegExp('/^NACK:[a-z0-9]+$/', $nack);
      $values[] = $nack;
    }
    sort($values);
    $this->assertEquals($values, array_unique($values));

    // The random token should at the start should same -- because we don't
    // the overhead of re-generating it frequently.
    $this->assertEquals(substr($values[0], 0, 37), substr($values[1], 0, 37));
  }

}
