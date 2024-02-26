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
   *
   * @group ornery
   */
  public function testLocalizedData(): void {
    $sqls = [
      'de_DE' => $this->getRenderedSql('de_DE'),
      'fr_FR' => $this->getRenderedSql('fr_FR'),
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

  private function getRenderedSql($locale) {
    $schema = new \CRM_Core_CodeGen_Schema(\Civi\Test::codeGen());
    $files = $schema->generateLocaleDataSql($locale);
    foreach ($files as $file => $content) {
      if (preg_match(';^civicrm_data\.;', $file)) {
        return $content;
      }
    }
    throw new \Exception("Failed to generate $locale");
  }

}
