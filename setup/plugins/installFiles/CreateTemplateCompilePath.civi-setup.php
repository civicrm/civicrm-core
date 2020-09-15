<?php
/**
 * @file
 *
 * Validate and create the template compile folder.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.checkRequirements', function (\Civi\Setup\Event\CheckRequirementsEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'checkRequirements'));
    $m = $e->getModel();

    if (empty($m->templateCompilePath)) {
      $e->addError('system', 'templateCompilePath', sprintf('The templateCompilePath is undefined.'));
    }
    else {
      $e->addInfo('system', 'templateCompilePath', 'The templateCompilePath is defined.');
    }

    if (!\Civi\Setup\FileUtil::isCreateable($m->templateCompilePath)) {
      $e->addError('system', 'templateCompilePathWritable', sprintf('The template compile dir "%s" cannot be created. Ensure the parent folder is writable.', $m->templateCompilePath));
    }
    else {
      $e->addInfo('system', 'templateCompilePathWritable', sprintf('The template compile dir "%s" can be created.', $m->templateCompilePath));
    }
  });

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installFiles', function (\Civi\Setup\Event\InstallFilesEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'installFiles'));
    $m = $e->getModel();

    if (!file_exists($m->templateCompilePath)) {
      Civi\Setup::log()->info('[CreateTemplateCompilePath.civi-setup.php] mkdir "{path}"', [
        'path' => $m->templateCompilePath,
      ]);
      mkdir($m->templateCompilePath, 0777, TRUE);
      \Civi\Setup\FileUtil::makeWebWriteable($m->templateCompilePath);
    }
  });
