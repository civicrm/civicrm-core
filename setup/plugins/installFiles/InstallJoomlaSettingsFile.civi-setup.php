<?php
/**
 * @file
 *
 * Generate the civicrm.settings.php file.
 *
 * The Joomla setup is unusual because it has two copies of the file, and they're
 * slightly different.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

/**
 * Read the $model and create the "civicrm.settings.php" files for Joomla.
 */
\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installFiles', function (\Civi\Setup\Event\InstallFilesEvent $e) {
    if ($e->getModel()->cms !== 'Joomla') {
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'installFiles'));

    $liveSite = substr_replace(JURI::root(), '', -1, 1);

    /**
     * @var \Civi\Setup\Model $m
     */
    $m = $e->getModel();
    $params = \Civi\Setup\SettingsUtil::createParams($m);
    $files = [
      'backend' => [
        'file' => $m->settingsPath,
        'params' => ['baseURL' => $liveSite . '/administrator/'] + $params,
      ],
      'frontend' => [
        'file' => implode(DIRECTORY_SEPARATOR, [JPATH_SITE, 'components', 'com_civicrm', 'civicrm.settings.php']),
        'params' => ['baseURL' => $liveSite . '/'] + $params,
      ],
    ];

    $tplPath = implode(DIRECTORY_SEPARATOR,
      [$m->srcPath, 'templates', 'CRM', 'common', 'civicrm.settings.php.template']
    );

    foreach ($files as $fileSpec) {
      $str = \Civi\Setup\SettingsUtil::evaluate($tplPath, $fileSpec['params']);
      JFile::write($fileSpec['file'], $str);
    }

  }, \Civi\Setup::PRIORITY_LATE);
