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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\Extension;

/**
 * This class contains generic upgrade logic which runs regardless of version.
 */
class CRM_Upgrade_Incremental_General {

  /**
   * The recommended PHP version.
   *
   * The point release will be dropped in recommendations unless it's .1 or
   * higher.
   */
  const RECOMMENDED_PHP_VER = '8.3.0';

  /**
   * The minimum recommended PHP version.
   *
   * A site running an earlier version will be told to upgrade.
   */
  const MIN_RECOMMENDED_PHP_VER = '8.1.0';

  /**
   * The minimum PHP version required to install Civi.
   */
  const MIN_INSTALL_PHP_VER = '8.0.0';

  /**
   * The minimum recommended MySQL version.
   *
   * A site running an earlier version will be encouraged to upgrade.
   */
  const MIN_RECOMMENDED_MYSQL_VER = '5.7';

  /**
   * The minimum MySQL version required to install Civi.
   */
  const MIN_INSTALL_MYSQL_VER = '5.7';

  /**
   * The minimum recommended MariaDB version.
   *
   * A site running an earlier version will be encouraged to upgrade.
   */
  const MIN_RECOMMENDED_MARIADB_VER = '10.4';

  /**
   * The minimum MariaDB version required to install Civi.
   */
  const MIN_INSTALL_MARIADB_VER = '10.2';

  /**
   * Compute any messages which should be displayed before upgrade.
   *
   * @param string $preUpgradeMessage
   *   alterable.
   * @param $currentVer
   * @param $latestVer
   */
  public static function setPreUpgradeMessage(&$preUpgradeMessage, $currentVer, $latestVer) {
    $dateFormat = Civi::Settings()->get('dateformatshortdate');
    $phpversion = phpversion();
    if (version_compare($phpversion, self::MIN_RECOMMENDED_PHP_VER) < 0) {
      $preUpgradeMessage .= '<p>';
      $preUpgradeMessage .= ts('This system uses PHP v%4. You may proceed with the upgrade, and CiviCRM v%1 will continue working normally. However, future releases will require PHP v%2. We recommend PHP v%3.', [
        1 => $latestVer,
        2 => self::MIN_RECOMMENDED_PHP_VER . '+',
        3 => preg_replace(';^(\d+\.\d+(?:\.[1-9]\d*)?).*$;', '\1', self::RECOMMENDED_PHP_VER) . '+',
        4 => $phpversion,
      ]);
      $preUpgradeMessage .= '</p>';
    }
    if (version_compare(CRM_Utils_SQL::getDatabaseVersion(), self::MIN_RECOMMENDED_MYSQL_VER) < 0) {
      $preUpgradeMessage .= '<p>';
      $preUpgradeMessage .= ts('This system uses MySQL/MariaDB v%5. You may proceed with the upgrade, and CiviCRM v%1 will continue working normally. However, CiviCRM v%4 will require MySQL v%2 or MariaDB v%3.', [
        1 => $latestVer,
        2 => self::MIN_RECOMMENDED_MYSQL_VER . '+',
        3 => self::MIN_RECOMMENDED_MARIADB_VER . '+',
        4 => '5.34' . '+',
        5 => CRM_Utils_SQL::getDatabaseVersion(),
      ]);
      $preUpgradeMessage .= '</p>';
    }

    // http://issues.civicrm.org/jira/browse/CRM-13572
    // Depending on how the code was upgraded, some sites may still have copies of old
    // source files left behind. This is often a forgivable offense, but it's quite
    // dangerous for CIVI-SA-2013-001.
    global $civicrm_root;
    $ofcFile = "$civicrm_root/packages/OpenFlashChart/php-ofc-library/ofc_upload_image.php";
    if (file_exists($ofcFile)) {
      if (@unlink($ofcFile)) {
        $preUpgradeMessage .= '<br />' . ts('This system included an outdated, insecure script (%1). The file was automatically deleted.', [
          1 => $ofcFile,
        ]);
      }
      else {
        $preUpgradeMessage .= '<br />' . ts('This system includes an outdated, insecure script (%1). Please delete it.', [
          1 => $ofcFile,
        ]);
      }
    }

    if (Civi::settings()->get('enable_innodb_fts')) {
      // The FTS indexing feature dynamically manipulates the schema which could
      // cause conflicts with other layers that manipulate the schema. The
      // simplest thing is to turn it off and back on.

      // It may not always be necessary to do this -- but I doubt we're going to test
      // systematically in future releases.  When it is necessary, one could probably
      // ignore the matter and simply run CRM_Core_InnoDBIndexer::fixSchemaDifferences
      // after the upgrade.  But that's speculative.  For now, we'll leave this
      // advanced feature in the hands of the sysadmin.
      $preUpgradeMessage .= '<br />' . ts('This database uses InnoDB Full Text Search for optimized searching. The upgrade procedure has not been tested with this feature. You should disable (and later re-enable) the feature by navigating to "Administer => Customize Data and Screens => Search Preferences".');
    }

    $snapshotIssues = CRM_Upgrade_Snapshot::getActivationIssues();
    if ($snapshotIssues) {
      $preUpgradeMessage .= '<details>';
      $preUpgradeMessage .= '<summary>' . ts('This upgrade will NOT use automatic snapshots.') . '</summary>';
      $preUpgradeMessage .= '<p>' . ts('If an upgrade problem is discovered in the future, automatic snapshots may help recover. However, they also require additional storage and may not be available or appropriate in all configurations.') . '</p>';
      $preUpgradeMessage .= ts('Here are the reasons why automatic snapshots are disabled:');
      $preUpgradeMessage .= '<ul>' . implode("", array_map(
          function($issue) {
            return sprintf('<li>%s</li>', $issue);
          }, $snapshotIssues)) . '</ul>';
      $preUpgradeMessage .= '<p>' . ts('You may enable snapshots in "<code>%1</code>" by setting the experimental option "<code>%2</code>".', [
        1 => 'civicrm.settings.php',
        2 => htmlentities('define(\'CIVICRM_UPGRADE_SNAPSHOT\', TRUE)'),
      ]) . '</p>';
      $preUpgradeMessage .= '</details>';
    }
  }

  /**
   * Perform any message template updates. 5.0+.
   * @param $message
   * @param $version version we are upgrading to
   * @param $fromVer version we are upgrading from
   */
  public static function updateMessageTemplate(&$message, $version, $fromVer) {
    if (version_compare($version, 5.0, '<')) {
      return;
    }
    $messageObj = new CRM_Upgrade_Incremental_MessageTemplates($version);
    $messages = $messageObj->getUpgradeMessages($fromVer);
    if (empty($messages)) {
      return;
    }
    $messagesHtml = array_map(function($k, $v) {
      return sprintf("<li><em>%s</em> - %s</li>", htmlentities($k), htmlentities($v));
    }, array_keys($messages), $messages);

    $message .= '<br />' . ts("The default copies of the message templates listed below will be updated to handle new features or correct a problem. Your installation has customized versions of these message templates, and you will need to apply the updates manually after running this upgrade. <a %1>View detailed instructions</a>.", [
      1 => 'href="https://docs.civicrm.org/user/en/latest/email/message-templates/#modifying-system-workflow-message-templates" target="_blank"',
    ]) . '<ul>' . implode('', $messagesHtml) . '</ul>';

    $messageObj->updateTemplates();
  }

  private static function isExtensionInstalled(string $key): bool {
    $extension = Extension::get(FALSE)
      ->addWhere('key', '=', $key)
      ->addWhere('status', '=', 'Installed')
      ->selectRowCount()
      ->execute();

    return $extension->countMatched() === 1;
  }

}
