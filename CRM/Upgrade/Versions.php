<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Upgrade version management
 */
class CRM_Upgrade_Versions {

  /**
   * @param string $version
   *   Ex: '5.22' or '5.22.3'
   *
   * @return CRM_Upgrade_Incremental_Base
   *   Ex: CRM_Upgrade_Incremental_php_FiveTwentyTwo
   */
  public static function getIncrementalPhpObject(string $version) {
    $versionParts = explode('.', $version);
    $versionName = CRM_Utils_EnglishNumber::toCamelCase($versionParts[0]) . CRM_Utils_EnglishNumber::toCamelCase($versionParts[1]);
    $className = "CRM_Upgrade_Incremental_php_{$versionName}";
    return new $className();
  }

  /**
   * @return array
   *   ex: ['5.13', '5.14', '5.15']
   */
  public static function incrementalPhpObjectVersions() {
    $versions = [];
    $phpDir = implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'Incremental', 'php']);
    $phpFiles = glob("$phpDir/*.php");
    foreach ($phpFiles as $phpFile) {
      $phpWord = substr(basename($phpFile), 0, -4);
      if (CRM_Utils_EnglishNumber::isNumeric($phpWord)) {
        /** @var \CRM_Upgrade_Incremental_Base $instance */
        $className = 'CRM_Upgrade_Incremental_php_' . $phpWord;
        $instance = new $className();
        $versions[] = $instance->getMajorMinor();
      }
    }
    usort($versions, 'version_compare');

    return $versions;
  }

  /**
   * Get a list of all patch-versions that appear in upgrade steps, whether
   * as *.mysql.tpl or as *.php.
   *
   * @return array
   * @throws Exception
   */
  public static function getRevisionSequence() {
    $revList = [];

    foreach (self::incrementalPhpObjectVersions() as $majorMinor) {
      $phpUpgrader = self::getIncrementalPhpObject($majorMinor);
      $revList = array_merge($revList, array_values($phpUpgrader->getRevisionSequence()));
    }

    usort($revList, 'version_compare');
    return $revList;
  }

  public static function getLatestVersion() {
    $xmlVersion = \CRM_Utils_System::version();

    $revisions = self::getRevisionSequence();
    $maxRevision = empty($revisions) ? NULL : end($revisions);
    if ($maxRevision && version_compare($xmlVersion, $maxRevision, '<')) {
      // e.g. running upgrader pre-release
      return $maxRevision;
    }
    return $xmlVersion;
  }

  /**
   * Determine the start and end version of the upgrade process.
   *
   * @return array(0=>$currentVer, 1=>$latestVer)
   */
  public static function getUpgradeVersions() {
    $latestVer = self::getLatestVersion();
    $currentVer = CRM_Core_BAO_Domain::version(TRUE);
    return [$currentVer, $latestVer];
  }

  public static function validateUpgradeVersions(string $currentVer, string $latestVer) {
    if (!$currentVer) {
      throw new CRM_Core_Exception(ts('Version information missing in civicrm database.'));
    }
    elseif (stripos($currentVer, 'upgrade')) {
      throw new CRM_Core_Exception(ts('Database check failed - the database looks to have been partially upgraded. You may want to reload the database with the backup and try the upgrade process again.'));
    }
    if (!$latestVer) {
      throw new CRM_Core_Exception(ts('Version information missing in civicrm codebase.'));
    }
  }

  public static function upgradesPending(): bool {
    [$dbVersion, $codeVersion] = self::getUpgradeVersions();
    return version_compare($dbVersion, $codeVersion) < 0;
  }

}
