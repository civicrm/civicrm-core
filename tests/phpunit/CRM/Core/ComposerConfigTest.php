<?php

/**
 * Class CRM_Core_ComposerConfigTest
 * @group headless
 */
class CRM_Core_ComposerConfigTest extends \PHPUnit\Framework\TestCase {

  /**
   * Assert that `composer.lock` remains as expected.
   *
   * Intentions:
   *  - In `civicrm-core`, the `composer.json` is permissive. It can be updated
   *    to support different versions of Symfony.
   *  - In `civicrm-core`, the `composer.lock` is less permissive, driven by
   *   the interests of existing D7/WP/J sites.
   *
   * Without this check, a well-meaning developer may upgrade the
   * `composer.lock`, and no one would notice the change in policy
   * because reviewers' eyes tend to gloss over `composer.lock`.
   */
  public function testHardLocks() {
    $hardLocks = [
      'symfony/config' => '/^v3\.4\./',
      'symfony/dependency-injection' => '/^v3\.4\./',
      'symfony/event-dispatcher' => '/^v3\.4\./',
      'symfony/filesystem' => '/^v3\.4\./',
      'symfony/finder' => '/^v3\.4\./',
      'symfony/process' => '/^v3\.4\./',
    ];

    $lockFile = Civi::paths()->getPath('[civicrm.root]/composer.lock');
    $lock = json_decode(file_get_contents($lockFile), 1);

    foreach ($lock['packages'] as $package) {
      if (isset($hardLocks[$package['name']])) {
        $this->assertRegExp($hardLocks[$package['name']], $package['version'],
          "Check hardlock for " . $package['name']);
        unset($hardLocks[$package['name']]);
      }
    }
    $this->assertEquals([], $hardLocks,
      'composer.lock should have references to all hardlocks');
  }

}
