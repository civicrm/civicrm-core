<?php
/**
 * @file
 *
 * Remove the civicrm.settings.php file.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.uninstallFiles', function (\Civi\Setup\Event\UninstallFilesEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'uninstallFiles'));

    $file = $e->getModel()->settingsPath;
    if (file_exists($file)) {
      if (!\Civi\Setup\FileUtil::isDeletable($file)) {
        throw new \Exception("Cannot remove $file");
      }
      unlink($file);
    }
  });
