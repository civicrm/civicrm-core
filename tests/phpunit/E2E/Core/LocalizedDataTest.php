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
   *
   * Note: As currently written, this test relies on the output of setup.sh/GenCode.
   * Consequently, if you're running locally while iterating on the code, you may find
   * the following command helps with your dev-test loop:
   *
   * $ env CIVICRM_LOCALES=en_US,fr_FR,de_DE ./bin/setup.sh -g \
   *   && phpunit6 tests/phpunit/E2E/Core/LocalizedDataTest.php
   */
  public function testLocalizedData() {
    $getSql = $this->getSqlFunc();

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

  /**
   * @return callable
   *   The SQL loader -- function(string $locale): string
   */
  private function getSqlFunc() {
    // Some deployment styles use stored files, and some generate SQL programmatically.
    // This heuristic discerns the style by UF name, although a better heuristic might be to check
    // for composer at CMS root. This works in a pinch.
    $uf = CIVICRM_UF;
    $installerTypes = [
      'Drupal' => [$this, '_getSqlFile'],
      'Drupal8' => [$this, '_getSqlLive'],
      'WordPress' => [$this, '_getSqlFile'],
      'Backdrop' => [$this, '_getSqlFile'],
      'Joomla' => [$this, '_getSqlFile'],
    ];
    if (isset($installerTypes[$uf])) {
      return $installerTypes[$uf];
    }
    else {
      throw new \RuntimeException("Failed to determine installation type for $uf");
    }
  }

  private function _getSqlFile($locale) {
    $path = \Civi::paths()->getPath("[civicrm.root]/sql/civicrm_data.{$locale}.mysql");
    $this->assertFileExists($path);
    return file_get_contents($path);
  }

  private function _getSqlLive($locale) {
    $schema = new \CRM_Core_CodeGen_Schema(\Civi\Test::codeGen());
    $files = $schema->generateLocaleDataSql($locale);
    foreach ($files as $file => $content) {
      if (preg_match(';^civicrm_data\.;', $file)) {
        return $content;
      }
    }
    throw new \Exception("Faield to generate $locale");
  }

}
