<?php
/**
 * @file
 *
 * Generate the civicrm.settings.php file.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

/**
 * Validate the $model.
 */
\Civi\Setup::dispatcher()
  ->addListener('civi.setup.checkRequirements', function(\Civi\Setup\Event\CheckRequirementsEvent $e) {
    $lang = \Civi\Setup::instance()->getModel()->lang;
    if ($lang && $lang != 'en_US') {
      // Use CiviCRM Core directory as a fall back.
      $baseDir = $e->getModel()->srcPath;
      if (isset($e->getModel()->paths['civicrm.private']['path'])) {
        // If the civicrm files directory is set use this as the base path.
        $baseDir = $e->getModel()->paths['civicrm.private']['path'];
      }
      // Set l10n basedir as a define. The GenCode.php tries to locate the l10n files
      // from this location if other than l10n in the civicrm core directory.
      if (!isset($e->getModel()->paths['civicrm.l10n']['path'])) {
        if (\CRM_Utils_Constant::value('CIVICRM_L10N_BASEDIR')) {
          $e->getModel()->paths['civicrm.l10n']['path'] = \CRM_Utils_Constant::value('CIVICRM_L10N_BASEDIR');
        }
        else {
          $e->getModel()->paths['civicrm.l10n']['path'] = $baseDir . DIRECTORY_SEPARATOR . 'l10n';
        }
      }
      if (getenv('CIVICRM_L10N_BASEDIR') === FALSE) {
        // Set the environment variable CIVICRM_L10N_BASEDIR which is used in xml/GenCode.php
        // to create the localized sql files.
        putenv('CIVICRM_L10N_BASEDIR=' . $e->getModel()->paths['civicrm.l10n']['path']);
      }
      if (!is_dir($e->getModel()->paths['civicrm.l10n']['path'])) {
        \Civi\Setup::log()->info("Creating directory: " . $e->getModel()->paths['civicrm.l10n']['path']);
        \CRM_Utils_File::createDir($e->getModel()->paths['civicrm.l10n']['path'], FALSE);
      }
    }
  }, \Civi\Setup::PRIORITY_MAIN);

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installFiles', function (\Civi\Setup\Event\InstallFilesEvent $e) {
    $lang = \Civi\Setup::instance()->getModel()->lang;
    if ($lang && $lang != 'en_US') {
      $downloadDir = $e->getModel()->paths['civicrm.l10n']['path'] . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . 'LC_MESSAGES';
      if (!is_dir($downloadDir)) {
        \Civi\Setup::log()->info("Creating directory: " . $downloadDir);
        \CRM_Utils_File::createDir($downloadDir, FALSE);
      }

      foreach ($e->getModel()->moFiles as $moFile => $url) {
        $l10DownloadFile = str_replace('[locale]', $lang, $url);
        \Civi\Setup::log()
          ->info("Download translation '.$moFile.' from " . $l10DownloadFile . ' into ' . $downloadDir);
        $client = new \GuzzleHttp\Client();
        $response = $client->get($l10DownloadFile);
        if ($response->getStatusCode() == 200) {
          $success = file_put_contents($downloadDir . DIRECTORY_SEPARATOR . $moFile, $response->getBody());
          if (!$success) {
            $e->addError('l10n', 'download', 'Unable to download translation file');
          }
        }
      }
    }
  }, \Civi\Setup::PRIORITY_MAIN);
