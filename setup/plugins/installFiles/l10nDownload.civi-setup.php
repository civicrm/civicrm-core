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
      // build list of candidate folders in preferred order
      $candidates = [];
      // if it's already set, that's our pref
      if (isset($e->getModel()->paths['civicrm.l10n']['path'])) {
        $candidates[] = $e->getModel()->paths['civicrm.l10n']['path'];
      }
      // Now check CIVICRM_L10N_BASEDIR via either define or env.
      // The GenCode.php tries to locate the l10n files
      // from this location if other than l10n in the civicrm core directory.
      $civicrm_l10n_basedir = CRM_Utils_Constant::value('CIVICRM_L10N_BASEDIR');
      if ($civicrm_l10n_basedir) {
        $candidates[] = $civicrm_l10n_basedir . DIRECTORY_SEPARATOR . 'l10n';
      }
      elseif (isset($e->getModel()->paths['civicrm.private']['path'])) {
        // If the civicrm files directory is set use this as the base path.
        $candidates[] = $e->getModel()->paths['civicrm.private']['path'] . DIRECTORY_SEPARATOR . 'l10n';
      }
      // Use CiviCRM Core directory as a fall back.
      $candidates[] = $e->getModel()->srcPath . DIRECTORY_SEPARATOR . 'l10n';

      // Now see if any of the folders already exist.
      foreach ($candidates as $candidate) {
        if (is_dir($candidate)) {
          $e->getModel()->paths['civicrm.l10n']['path'] = $candidate;
          break;
        }
      }
      // If none existed, then take our first preference. We know there's always at least one.
      if (!isset($e->getModel()->paths['civicrm.l10n']['path'])) {
        $e->getModel()->paths['civicrm.l10n']['path'] = $candidates[0];
      }

      if (getenv('CIVICRM_L10N_BASEDIR') === FALSE) {
        // Set the environment variable CIVICRM_L10N_BASEDIR which is used in xml/GenCode.php
        // to create the localized sql files.
        putenv('CIVICRM_L10N_BASEDIR=' . $e->getModel()->paths['civicrm.l10n']['path']);
      }
      if (!is_dir($e->getModel()->paths['civicrm.l10n']['path'])) {
        \Civi\Setup::log()->info("Creating directory: " . $e->getModel()->paths['civicrm.l10n']['path']);
        if (!mkdir($e->getModel()->paths['civicrm.l10n']['path'], 0777, TRUE)) {
          $e->addError('system', 'l10nWritable', sprintf('Unable to create l10n directory "%s"', $e->getModel()->paths['civicrm.l10n']['path']));
        }
      }

      $downloadDir = $e->getModel()->paths['civicrm.l10n']['path'] . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . 'LC_MESSAGES';
      if (!is_dir($downloadDir)) {
        \Civi\Setup::log()->info("Creating directory: " . $downloadDir);
        if (!mkdir($downloadDir, 0777, TRUE)) {
          $e->addError('system', 'l10nWritable', sprintf('Unable to create language directory "%s"', $downloadDir));
        }
      }

      foreach ($e->getModel()->moFiles as $moFile => $url) {
        $l10DownloadFile = str_replace('[locale]', $lang, $url);
        $destFile = $downloadDir . DIRECTORY_SEPARATOR . $moFile;
        if (!file_exists($destFile)) {
          \Civi\Setup::log()
            ->info("Download translation '$moFile' from " . $l10DownloadFile . ' into ' . $downloadDir);
          $client = new \GuzzleHttp\Client();
          $response = $client->get($l10DownloadFile);
          if ($response->getStatusCode() == 200) {
            $success = file_put_contents($destFile, $response->getBody());
            if ($success) {
              $e->isReloadRequired(TRUE);
            }
            else {
              $e->addError('l10n', 'download', 'Unable to download translation file' . $destFile);
            }
          }
        }
      }
    }
  }, \Civi\Setup::PRIORITY_MAIN);
