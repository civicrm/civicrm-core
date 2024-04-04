<?php
/**
 * @file
 *
 * Validate and create the civicrm.files folder.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.checkRequirements', function (\Civi\Setup\Event\CheckRequirementsEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'checkRequirements'));
    $m = $e->getModel();

    if ($m->cms !== 'Standalone') {
      // The installers on WP+J don't prepopulate $m->paths['civicrm.files'].
      // TODO: Maybe they should? It would probably be good for all UF's to follow same codepaths for setting up the folder.
      return;
    }

    $civicrmFilesDirectory = $m->paths['civicrm.files']['path'] ?? '';

    if (!$civicrmFilesDirectory) {
      $e->addError('system', 'civicrmFilesPath', 'The civicrm.files directory path is undefined.');
    }
    else {
      $e->addInfo('system', 'civicrmFilesPath', sprintf('The civicrm.files directory path is defined ("%s").', $civicrmFilesDirectory));
    }

    if ($civicrmFilesDirectory && !file_exists($civicrmFilesDirectory) && !\Civi\Setup\FileUtil::isCreateable($civicrmFilesDirectory)) {
      $e->addError('system', 'civicrmFilesPathWritable', sprintf('The civicrm files dir "%s" does not exist and cannot be created. Ensure it exists or the parent folder is writable.', $civicrmFilesDirectory));
    }
    elseif ($civicrmFilesDirectory && !file_exists($civicrmFilesDirectory)) {
      $e->addInfo('system', 'civicrmFilesPathWritable', sprintf('The civicrm files dir "%s" can be created.', $civicrmFilesDirectory));
    }
  });

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installFiles', function (\Civi\Setup\Event\InstallFilesEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'installFiles'));
    $m = $e->getModel();

    $civicrmFilesDirectory = $m->paths['civicrm.files']['path'] ?? '';

    if ($civicrmFilesDirectory && !file_exists($civicrmFilesDirectory)) {
      Civi\Setup::log()->info('[StandaloneCivicrmFilesPath.civi-setup.php] mkdir "{path}"', [
        'path' => $civicrmFilesDirectory,
      ]);
      mkdir($civicrmFilesDirectory, 0777, TRUE);
      \Civi\Setup\FileUtil::makeWebWriteable($civicrmFilesDirectory);
    }
  });
