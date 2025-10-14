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
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'checkRequirements'));

    /**
     * @var \Civi\Setup\Model $m
     */
    $m = $e->getModel();

    if (empty($m->settingsPath)) {
      $e->addError('system', 'settingsPath', sprintf('The settingsPath is undefined.'));
    }
    else {
      $e->addInfo('system', 'settingsPath', sprintf('The settingsPath is defined.'));
    }

    // If Civi is already installed, Drupal 8's status report page also calls us
    // and so we need to modify the check slightly since we want the reverse
    // conditions.
    $installed = \Civi\Setup::instance()->checkInstalled();
    $alreadyInstalled = $installed->isSettingInstalled() || $installed->isDatabaseInstalled();

    if (!\Civi\Setup\FileUtil::isCreateable($m->settingsPath)) {
      if ($alreadyInstalled) {
        $e->addInfo('system', 'settingsWritable', sprintf('The settings file "%s" is protected from writing.', $m->settingsPath));
      }
      else {
        $e->addError('system', 'settingsWritable', sprintf('The settings file "%s" cannot be created. Ensure the parent folder is writable.', $m->settingsPath));
      }
    }
    else {
      if ($alreadyInstalled) {
        // Note if we were to output an error, we wouldn't be able to use
        // `cv core:install` to do an in-place reinstall since it would fail
        // requirements checks.
        $e->addWarning('system', 'settingsWritable', sprintf('The settings file "%s" should not be writable.', $m->settingsPath));
      }
      else {
        $e->addInfo('system', 'settingsWritable', sprintf('The settings file "%s" can be created.', $m->settingsPath));
      }
    }
  });

/**
 * Read the $model and create the "civicrm.settings.php".
 */
\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installFiles', function (\Civi\Setup\Event\InstallFilesEvent $e) {
    if ($e->getModel()->cms === 'Joomla') {
      // Complicated. Another plugin will do it.
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'installFiles'));

    /**
     * @var \Civi\Setup\Model $m
     */
    $m = $e->getModel();
    $params = \Civi\Setup\SettingsUtil::createParams($m);

    $parent = dirname($m->settingsPath);
    if (!file_exists($parent)) {
      Civi\Setup::log()->info('[InstallSettingsFile.civi-setup.php] mkdir "{path}"', ['path' => $parent]);
      mkdir($parent, 0777, TRUE);
      \Civi\Setup\FileUtil::makeWebWriteable($parent);
    }

    // And persist it...
    $tplPath = implode(DIRECTORY_SEPARATOR,
      [$m->srcPath, 'templates', 'CRM', 'common', 'civicrm.settings.php.template']
    );
    $str = \Civi\Setup\SettingsUtil::evaluate($tplPath, $params);

    if (!$m->doNotCreateSettingsFile) {
      file_put_contents($m->settingsPath, $str);
    }

  }, \Civi\Setup::PRIORITY_LATE);
