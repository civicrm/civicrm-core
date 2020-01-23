<?php

namespace E2E\Core;

/**
 * Class LocalizedDataTest
 * @package E2E\Core
 * @group e2e
 */
class LocalizedDataTest extends \CiviEndToEndTestCase {

  /**
   * Smoke test to check that "civicrm_data*.mysql" files contain
   * translated strings.
   */
  public function testLocalizedData() {
    $getSql = function($locale) {
      $path = \Civi::paths()->getPath("[civicrm.root]/sql/civicrm_data.{$locale}.mysql");
      $this->assertFileExists($path);
      return file_get_contents($path);
    };
    $sqls = [
      'de_DE' => $getSql('de_DE'),
      'fr_FR' => $getSql('fr_FR'),
    ];
    $pats = [
      'de_DE' => '/new_organization.*Neue Organisation/i',
      'fr_FR' => '/new_organization.*Nouvelle organisation/i',
    ];

    $match = function($sqlLocale, $patLocale) use ($pats, $sqls) {
      return (bool) preg_match($pats[$patLocale], $sqls[$sqlLocale]);
    };

    $this->assertTrue($match('de_DE', 'de_DE'), 'The German SQL should match the German pattern.');
    $this->assertTrue($match('fr_FR', 'fr_FR'), 'The French SQL should match the French pattern.');
    $this->assertFalse($match('de_DE', 'fr_FR'), 'The German SQL should not match the French pattern.');
    $this->assertFalse($match('fr_FR', 'de_DE'), 'The French SQL should not match the German pattern.');
  }

}
