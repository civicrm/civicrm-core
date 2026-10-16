<?php

/**
 * @group headless
 */
class CRM_Upgrade_Incremental_GeneralTest extends CiviUnitTestCase {

  /**
   * @return array
   */
  public static function databaseVersions(): array {
    return [
      'MySQL below recommended' => ['5.7.44-log', TRUE],
      'MySQL recommended' => ['8.0.36', FALSE],
      'MariaDB below recommended' => ['10.3.39-MariaDB-0+deb10u2', TRUE],
      'MariaDB recommended' => ['10.11.6-MariaDB-0+deb12u1', FALSE],
    ];
  }

  /**
   * MySQL and MariaDB are each compared with their own recommended version.
   *
   * @dataProvider databaseVersions
   */
  public function testDatabaseVersionMessage(string $version, bool $expectWarning): void {
    $original = CRM_Utils_SQL::getDatabaseVersion();
    Civi::$statics['CRM_Utils_SQL::getDatabaseVersion'] = $version;
    try {
      $message = '';
      CRM_Upgrade_Incremental_General::setPreUpgradeMessage($message, '6.20.0', '6.21.0');
    }
    finally {
      Civi::$statics['CRM_Utils_SQL::getDatabaseVersion'] = $original;
    }
    $this->assertSame($expectWarning, str_contains($message, 'will require MySQL'));
  }

}
